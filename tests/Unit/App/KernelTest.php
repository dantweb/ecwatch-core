<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\Tests\Unit\App;

use Dantweb\Ecommwatch\App\EcwWatchKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KernelTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        $kernel = new EcwWatchKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();
        $this->assertInstanceOf(
            \Symfony\Component\DependencyInjection\ContainerInterface::class,
            $container
        );

        $kernel->shutdown();
    }
}
