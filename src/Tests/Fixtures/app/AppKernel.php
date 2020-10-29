<?php


namespace Drivenow\AsyncWorkersBundle\Tests\Fixtures;


use Drivenow\AsyncWorkersBundle\AsyncWorkersBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @return array|iterable|\Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new AsyncWorkersBundle(),
            new MonologBundle(),
        ];


        return $bundles;
    }


    /**
     * @param LoaderInterface $loader
     *
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/' . $this->environment . '.yml');

    }
}