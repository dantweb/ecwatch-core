#!/bin/bash

# Load environment variables from .env file
if [ -f tests/.env ]; then
  export $(grep -v '^#' tests/.env | xargs)
else
  echo ".env file not found! Please make sure it exists in the current directory."
  exit 1
fi

# Execute the PHP script to run the trait methods
php <<'PHP'
<?php

require "/app/core/vendor/autoload.php";

use Dantweb\Ecommwatch\Tests\Unit\DemoDataImportTrait;

class TestDatabaseSetup
{
    use DemoDataImportTrait;

    public function __construct()
    {
        $this->init();
    }

    public function run(): void
    {
        echo "Running migrations...\n";
        $this->doMigrations();
        echo "Importing demo data...\n";
        $this->importDemoData();
        echo "Setup complete.\n";
    }
}

$setup = new TestDatabaseSetup();
$setup->run();

PHP