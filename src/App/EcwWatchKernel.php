<?php

declare(strict_types=1);

namespace Dantweb\Ecommwatch\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;

class EcwWatchKernel extends Kernel implements KernelInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        // Still need at least FrameworkBundle for the Container & console commands:
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
    }

    public function getProjectDir(): string
    {
        // e.g. /app/core
        return dirname(__DIR__, 2);
    }

    private function getConfigDir(): string
    {
        // e.g. /app/core/config
        return $this->getProjectDir() . '/config';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $conf = $this->getConfigDir();
        // load your packages (if any)
        $loader->load($conf . '/packages/*.yaml', 'glob');
        // load main services
        $loader->load($conf . '/services.yaml', 'yaml');
        // load environment‑specific overrides (for `test` loads services_test.yaml)
        $loader->load($conf . '/services_' . $this->environment . '.yaml', 'yaml');
    }
}
