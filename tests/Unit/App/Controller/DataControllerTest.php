<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\App\Controller;

use Dantweb\Ecommwatch\App\EcwWatchKernel;
use Dantweb\Ecommwatch\Framework\Service\ExpressionResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;

class DataControllerTest extends TestCase
{

    protected static string $testDir = "/../../../_data/model_migration_test/";
    protected string $importDataDir = '';

    // List the tables that the migration creates.
    protected static array $tablesToCleanup = [
        'BaseOrderModel',
        'CustomerModel',
        'ShipmentModel',
    ];

    private KernelInterface $kernel;
    private \Symfony\Component\DependencyInjection\ContainerInterface $container;

    protected function setUp(): void
    {
        $this->kernel = new EcwWatchKernel('test', true);
        $this->kernel->boot();
        $this->container = $this->kernel->getContainer();
    }


    private ExpressionResolver $expressionResolver;

    public function testExpressionResolverServiceIsWired(): void
    {
        $resolver = $this->container->get(ExpressionResolver::class);
        $this->assertInstanceOf(ExpressionResolver::class, $resolver);
    }

    public function testResolveDailyOrderCount(): void
    {
        /** @var ExpressionResolver $resolver */
        $resolver = $this->container->get(ExpressionResolver::class);

        // Using the same date format your service expects (d.m.Y)
        $expression = 'daily(BaseOrderModel.count())';
        $start      = '01.01.2024';
        $end        = '02.01.2024';

        $result = $resolver->resolve($expression, $start, $end);

        // Should be an array of floats, one bucket per day
        $this->assertIsArray($result);
        $this->assertCount(1, $result, 'Expected exactly one daily bucket between the two dates');
        $this->assertIsFloat(reset($result), 'Each bucket value should be a float');

        $expression = 'daily(BaseOrderModel.count())';
        $start      = '01.01.2024';
        $end        = '03.01.2024';

        $result = $resolver->resolve($expression, $start, $end);
        $this->assertCount(2, $result);
    }
}
