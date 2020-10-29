<?php


namespace Drivenow\AsyncWorkersBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Drivenow\AsyncWorkersBundle\Tests\Fixtures\AppKernel;

abstract class TestCase extends BaseWebTestCase
{
    /**
     * @param array $options
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer(array $options = [])
    {
        if (!static::$kernel) {
            static::$kernel = static::createKernel($options);
        }
        static::$kernel->boot();

        return static::$kernel->getContainer();
    }


    /**
     * @return string
     */
    protected static function getKernelClass()
    {
        require_once __DIR__ . '/Fixtures/app/AppKernel.php';

        return AppKernel::class;
    }


    /**
     * @param array $options
     *
     * @return mixed|\Symfony\Component\HttpKernel\KernelInterface
     */
    protected static function createKernel(array $options = [])
    {
        $class = self::getKernelClass();

        return new $class(
            'test',
            isset($options['debug']) ? $options['debug'] : true
        );
    }


    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}