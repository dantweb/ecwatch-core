<?php


declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\Framework\Middleware;

use App\Modules\Atomizer\src\EcwModel\AbstractEcwModel;
use App\Modules\Atomizer\src\EcwModel\EcwModelInterface;
use Dantweb\Ecommwatch\Framework\Middleware\Migration;
use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;

final class RepoWritingModeTest extends \PHPUnit\Framework\TestCase
{
    use DemoDataImportTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->init();
        $this->ecwModel = $this->getTestEcwModel();
        $this->prepareTestMigration();
    }

    protected function tearDown(): void
    {
        $this->dbConnect->getDb()->exec("DROP TABLE IF EXISTS test_table");
    }

    /**
     * @throws \Exception
     */
    protected function prepareTestMigration(): void
    {
        $migration = new Migration($this->dbConnect);
        $initialMigrationSql = $migration->createMigration($this->ecwModel);
        if (!empty($initialMigrationSql)) {
            $migration->run($initialMigrationSql);
        }
    }

    /**
     * 1) Saving a new, non‑existing model should insert one row
     *    and make it findable by findBy().
     */
    public function testSaveNewModelInsertsAndFindsIt(): void
    {

        $model = $this->getTestEcwModel();
        $repo = $this->repoFactory->getRepo($model);

        // any mode works for a brand‑new record
        $repo->setWritingMode('duplicates_report');

        $model->setOrder('1234567890');
        $model->setName('testName');
        $model->setEmail('email@example.com');
        $repo->save($model);

        $all = $repo->findAll();
        $this->assertCount(1, $all, "One row should have been inserted");

        $found = $repo->findBy(['order' => '1234567890']);
        $this->assertCount(1, $found, "findBy should return exactly one result");
        $this->assertEquals('testName', $found[0]->get('name'));
    }

    /**
     * 2) duplicates_override: saving an already‑existing model should
     *    update that row (not insert), so count remains 1 and fields are updated.
     */
    public function testSaveDuplicateWithOverrideUpdatesExisting(): void
    {
        $model = $this->getTestEcwModel();
        $repo = $this->repoFactory->getRepo($model);
        $repo->setWritingMode('duplicates_override');

        // first insert
        $model->setOrder(1234567890);
        $model->setName('testName');
        $model->setEmail('email@example.com');
        $repo->save($model);

        // now change only the int field and save again
        $model->setName('newName');
        $repo->save($model);

        // still only one row
        $this->assertCount(1, $repo->findAll());
        // and its value was overridden
        $found = $repo->findBy(['order' => 1234567890]);
        $this->assertEquals('newName', $found[0]->get('name'));
    }

    /**
     * 3) duplicates_report: saving a duplicate should leave the row unchanged
     *    (and not insert a second), and should not throw an exception.
     */
    public function testSaveDuplicateWithReportKeepsOriginal(): void
    {
        $model = clone $this->ecwModel;
        $repo = $this->repoFactory->getRepo($this->ecwModel);
        $repo->setWritingMode('duplicates_report');

        // first insert
        $model->setOrder(1234567890);
        $model->setName('testName');
        $model->setEmail('email@example.com');
        $repo->save($model);

        // attempt to insert a “duplicate” with a different int value
        $second = $this->getTestEcwModel();
        $second->setOrder(1234567890);
        $second->setName('anotherName');

        // should not throw, should still be exactly one row and remain 5
        $repo->save($second);

        $this->assertCount(1, $repo->findAll());
        $found = $repo->findBy(['order' => 1234567890]);
        $this->assertEquals('testName', $found[0]->getName(), "Original row should remain untouched");
    }

    /**
     * 4) add_ignore_duplicates: saving a duplicate should insert a new row,
     *    so count goes from 1 to 2.
     */
    public function testSaveDuplicateWithAddIgnoreDuplicatesAlwaysInserts(): void
    {
        $model = clone $this->ecwModel;
        $repo = $this->repoFactory->getRepo($model);
        $repo->setWritingMode('add_ignore_duplicates');

        // first insert
        $model->setOrder('123');
        $model->setName('testName3');
        $model->setEmail('gustav@example.com');
        $repo->save($model);

        // attempt to insert a “duplicate” with a different int value
        $second = $this->getTestEcwModel();
        $second->setOrder('456');
        $second->setEmail('walter@example.com');
        $repo->save($second);

        // now there should be two rows—IDs will differ
        $all = $repo->findAll();
        $this->assertCount(2, $all, "In add_ignore_duplicates mode every save() must insert");
        $this->assertNotEquals(
            $all[0]->getOrder(),
            $all[1]->getOrder(),
            "Two distinct rows should exist"
        );
    }

    private function createTestModel(string $tableName, array $fields): AbstractEcwModel
    {
        return new class($tableName, $fields) extends AbstractEcwModel {
            // Any additional method overrides if needed
        };
    }

    private function getTestEcwModel(): EcwModelInterface
    {
        return $this->createTestModel('test_table', [
            'order' => ['type' => 'int', 'unique' => true],
            'name' => ['type' => 'varchar(255)'],
            'email' => ['type' => 'varchar(100)'],
            'created_at' => ['type' => 'timestamp']
        ]);
    }
}