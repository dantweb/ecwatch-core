<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Framework\Middleware;

use Dantweb\Ecommwatch\Framework\Middleware\DatabaseConnector;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    private ?DatabaseConnector $connector = null;

    protected function setUp(): void
    {
        $this->connector = DatabaseConnector::getInstance();
    }

    public function testDatabaseConnection()
    {
        $db = $this->connector->getDb();

        $this->assertInstanceOf(PDO::class, $db);
        $this->assertTrue($db->getAttribute(PDO::ATTR_CONNECTION_STATUS) !== null);
    }

    public function testCanExecuteShowTables()
    {
        $db = $this->connector->getDb();

        try {
            $statement = $db->query('SHOW TABLES');
            $this->assertIsArray($statement->fetchAll());
        } catch (PDOException $e) {
            $this->fail('Unable to execute SHOW TABLES: ' . $e->getMessage());
        }
    }

    public function testConnectionErrorHandling()
    {
        // Test with intentionally incorrect credentials
        $this->expectException(PDOException::class);

        DatabaseConnector::getInstance(
            'non-existent-host',
            'invalid-user',
            'wrong-password',
            'non-existent-db'
        );
    }

    public function testPDOErrorMode()
    {
        $db = $this->connector->getDb();

        // Verify error mode is set to throw exceptions
        $this->assertEquals(
            PDO::ERRMODE_EXCEPTION,
            $db->getAttribute(PDO::ATTR_ERRMODE)
        );
    }
}