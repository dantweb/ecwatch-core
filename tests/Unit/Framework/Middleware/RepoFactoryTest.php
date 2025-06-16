<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware;

use App\Modules\Atomizer\src\EcwModel\AbstractEcwModel;
use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Exception\EcwTableNotFoundException;
use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Dantweb\Ecommwatch\Framework\Middleware\RepoFactory\RepoFactory;
use Dantweb\Ecommwatch\Framework\Middleware\Repository\AbstractRepo;

final class RepoFactoryTest extends \PHPUnit\Framework\TestCase
{
    private string $yaml;
    private DatabaseConnector $dbConnect;
    private RepoFactory $repoFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->yaml = $this->getYamlModelConfig();
        $this->dbConnect = DatabaseConnector::getInstance();
        $this->repoFactory = new RepoFactory($this->dbConnect);
    }

    /**
     * Tests that requesting a repository for a non‐existing table throws an exception.
     *
     * @throws EcwTableNotFoundException
     */
    public function testCheckingNonExistingRepo(): void
    {
        $this->controlTestDbCleanUp();
        $this->expectException(EcwTableNotFoundException::class);
        // No migration is run so the table does not exist.
        $this->repoFactory->getRepo(new TestModelClass());
    }

    /**
     * Tests that after migration, saving one model results in one entry from findAll.
     */
    public function testRepoHasItems(): void
    {
        $this->runMigration();
        $repo = $this->repoFactory->getRepo(new TestModelClass());
        $this->assertInstanceOf(AbstractRepo::class, $repo);

        $model = new TestModelClass();
        $model->setTestField('test1');
        $model->setTestIntField(57243597);
        $repo->save($model);

        $repoNew = $this->repoFactory->getRepo(new TestModelClass());
        $entries = $repoNew->findAll();
        $this->assertNotEmpty($entries);
        $this->assertCount(1, $entries);
    }

    /**
     * Tests that the where() method correctly returns the model with matching field value.
     */
    public function testRepoHasItemsWithWhere(): void
    {
        $this->runMigration();
        $repo = $this->repoFactory->getRepo(new TestModelClass());

        $model1 = new TestModelClass();
        $model1->setTestField('test1');
        $model1->setTestIntField(137);
        $repo->save($model1);

        $model2 = new TestModelClass();
        $model2->setTestField('test2');
        $model2->setTestIntField(-463434);
        $repo->save($model2);

        $repo2 = $this->repoFactory->getRepo(new TestModelClass());
        // Using "=" operator as string
        $item = $repo2->where('test_field', '=', 'test2');
        $this->assertInstanceOf(EcwModelInterface::class, $item);
        $this->assertEquals('test2', $item->getTestField());
        $this->assertEquals(-463434, $item->getTestIntField());
    }

    /**
     * Tests a condition with an operator.
     */
    public function testRepoHasItemsWithConditions(): void
    {
        $this->runMigration();
        $repo = $this->repoFactory->getRepo(new TestModelClass());

        $model1 = new TestModelClass();
        $model1->setTestField('test1');
        $model1->setTestIntField(1);
        $repo->save($model1);

        $model2 = new TestModelClass();
        $model2->setTestField('test2');
        $model2->setTestIntField(2);
        $repo->save($model2);

        $repoTest = $this->repoFactory->getRepo(new TestModelClass());
        $item = $repoTest->where('test_int_field', '>', 1);
        $this->assertInstanceOf(EcwModelInterface::class, $item);
        $this->assertEquals('test2', $item->getTestField());
    }

    /**
     * Tests that a record can be replaced.
     */
    public function testRepoReplace(): void
    {
        $this->runMigration();
        $repo = $this->repoFactory->getRepo(new TestModelClass());

        $model1 = new TestModelClass();
        $model1->setTestField('test1');
        $model1->setTestIntField(1);
        $repo->save($model1);

        $model2 = new TestModelClass();
        $model2->setTestField('test2');
        $model2->setTestIntField(2);
        $repo->save($model2);

        $model3 = new TestModelClass();
        $model3->setTestField('test3');
        $model3->setTestIntField(3);
        $repo->save($model3);

        $model4 = new TestModelClass();
        $model4->setTestField('test99');
        $model4->setTestIntField(99);
        $repo->save($model4);

        $testRepo = $this->repoFactory->getRepo(new TestModelClass());
        // Replace the record where test_field equals 'test2'
        $item = $testRepo->where('test_field', '=', 'test99');
        $this->assertEquals(99, $item->getTestIntField());
    }

    /**
     * Tests repository operations by creating 100 rows of random data and then invoking add,
     * findBy, findAll, findById, and where.
     */
    public function testRepoOperationsWithBulkData(): void
    {
        $this->runMigration();

        $r1 = $this->repoFactory->getRepo(new TestModelClass());
        // Create 100 rows with sequential data
        for ($i = 1; $i <= 100; $i++) {
            $model = new TestModelClass();
            $model->setTestField("test_$i");
            $model->setTestIntField($i);
            $r1->save($model);
        }

        unset($r1);

        $repo = $this->repoFactory->getRepo(new TestModelClass());
        $all = $repo->findAll();
        $this->assertCount(100, $all);

        // Test findById (assuming the first field is the auto-increment identifier)
        $sampleId = 50;
        $sampleItem = $repo->findById($sampleId);
        $this->assertNotEmpty($sampleItem);
        $this->assertEquals("test_50", $sampleItem->getTestField());

        // Test findBy method for a specific field value
        $found = $repo->findBy(['test_field' => 'test_75']);
        $this->assertNotEmpty($found);
        $this->assertEquals('test_75', $found[0]->getTestField());

        // Test findBy method with multiple conditions
        $found = $repo->findBy([
            'test_field' => 'test_75',
            'test_int_field' => 75
        ]);
        $this->assertNotEmpty($found);
        $this->assertEquals('test_75', $found[0]->getTestField());
        $this->assertEquals(75, $found[0]->getTestIntField());

        // Test findBy with limit
        $limitedResults = $repo->findBy(['test_int_field' => ['>', 90]], limit: 5);
        $this->assertCount(5, $limitedResults);
        foreach ($limitedResults as $result) {
            $this->assertGreaterThan(90, $result->getTestIntField());
        }
    }

    private function getYamlModelConfig(): string
    {
        return file_get_contents(__DIR__ . '/../../../_data/TestModelConfig.yaml');
    }

    /**
     * Runs the migration using the TestModelClass definition.
     * @throws \Exception
     */
    private function runMigration(): void
    {
        $model = new TestModelClass();
        $migration = new Migration($this->dbConnect);
        $migrationSql = $migration->createMigration($model);

        if (empty($migrationSql)) {
            throw new \Exception("Migration SQL is empty");
        }

        $migration->run($migrationSql);
    }

    protected function tearDown(): void
    {
        $this->controlTestDbCleanUp();
        parent::tearDown();
    }

    protected function controlTestDbCleanUp()
    {
        $pdo = $this->dbConnect->getDb();
        $pdo->exec("DROP TABLE IF EXISTS test_table");
    }
}

/**
 * Test model class implementing the expected table structure.
 */
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

class TestModelClass extends AbstractEcwModel
{
    public function __construct()
    {
        parent::__construct(
            'test_table',
            [
                'id'             => ['type' => 'int', 'primary' => true, 'auto_increment' => true],
                'test_field'     => ['type' => 'varchar(255)'],
                'test_int_field' => ['type' => 'int'],
            ]
        );
    }
}
