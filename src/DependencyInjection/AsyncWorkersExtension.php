<?php

namespace Drivenow\AsyncWorkersBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AsyncWorkersExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $exampleConfig = $this->getExampleWorkers();
        if ($config !== null && is_array($config)) {
            $workers = array_merge($config, $exampleConfig);
        } else {
            $workers = $exampleConfig;
        }
        $container->setParameter('async_workers', $workers);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }


    /**
     * @return array
     */
    private function getExampleWorkers()
    {
        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $pathToExample = $fileLocator->locate('example_worker.yml');
        if (method_exists(Yaml::class, 'parseFile')) {
            $exampleConfig = Yaml::parseFile($pathToExample);
        } else {
            $fileContent = file_get_contents($pathToExample);
            $exampleConfig = Yaml::parse($fileContent);
        }

        return $exampleConfig['async_workers'];
    }
}
