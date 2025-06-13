<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Framework\Middleware;

use PDO;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;

class DatabaseConnector
{
    private static ?DatabaseConnector $instance = null;

    protected ?PDO $db;
    protected string $dbHost;
    protected string $dbUser;
    protected string $dbPassword;
    protected string $dbName;
    protected int $dbPort;

    /**
     * @var array|false|mixed|string
     */
    protected mixed $dbEngine;

    public function __construct(
        string $dbHost = '',
        string $dbUser = '',
        string $dbPassword = '',
        string $dbName = '',
        int $dbPort = 3306,
        string $writingMode = 'duplicates_report'
    ) {
        $rootDir = dirname(__DIR__, 3);

        $envFile[0] = Path::join($rootDir , '.env');
        $envFile[1] = Path::join($rootDir , 'tests', '.env');
        $dotenv = new Dotenv();
        if (file_exists($envFile[1]) && $this->isTestMode()) {
            $dotenv->overload($envFile[1]);
        } elseif (file_exists($envFile[0])) {
            $dotenv->overload($envFile[0]);
        }

        $this->dbHost = !empty($dbHost)
            ? $dbHost
            : ($_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost');

        $this->dbUser = !empty($dbUser)
            ? $dbUser
            : ($_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? '');

        $this->dbPassword = !empty($dbPassword)
            ? $dbPassword
            : ($_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '');

        $this->dbName = !empty($dbName)
            ? $dbName
            : ($_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? '');

        $this->dbPort = !empty($dbPort)
            ? $dbPort
            : ((int)$_ENV['MYSQL_PORT'] ?? (int)getenv('MYSQL_PORT') ?? 3306);

        $this->dbEngine = $_ENV['DB_ENGINE'] ?? getenv('DB_ENGINE') ?? 'mysql';

        // Set default writing mode or validate the provided mode
        $this->connect();
    }

    public static function getInstance(string $dbHost = '',
                                       string $dbUser = '',
                                       string $dbPassword = '',
                                       string $dbName = '',
                                       int $dbPort = 3306,
                                       string $writingMode = 'duplicates_report'): self
    {
        if (self::$instance === null) {
            self::$instance = new self( $dbHost, $dbUser, $dbPassword, $dbName, $dbPort, $writingMode);
        }

        $instance = self::$instance;

        if ($dbHost !== '' && $instance->dbHost !== $dbHost ||
            $dbUser !== '' && $instance->dbUser !== $dbUser ||
            $dbPassword !== '' && $instance->dbPassword !== $dbPassword ||
            $dbName !== '' && $instance->dbName !== $dbName ||
            $dbPort !== 3306 && $instance->dbPort !== $dbPort) {
            return new self( $dbHost, $dbUser, $dbPassword, $dbName, $dbPort, $writingMode);
        }

        return self::$instance;
    }

    public function getDb(): ?PDO
    {
        return $this->db;
    }

    private function connect(): DatabaseConnector
    {
        $this->db = new PDO(
            'mysql:host=' . $this->dbHost
            . ';dbname=' . $this->dbName
            . ';port=' . $this->dbPort,
            $this->dbUser,
            $this->dbPassword
        );

        return $this;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function disconnect(): void
    {
        $this->db = null;
    }

    protected function isTestMode(): bool
    {
        return isset($_ENV['PHP_UNIT']) && $_ENV['PHP_UNIT']==true;
    }
}