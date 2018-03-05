<?php declare(strict_types=1);

namespace TM\JsonApiBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use TM\JsonApiBundle\Request\Configuration\ConfigurationInterface;

class ControllerListener
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws \ReflectionException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_callable($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (!is_array($controller)) {
            return;
        }

        $object = new \ReflectionClass(ClassUtils::getClass($controller[0]));
        $method = $object->getMethod($controller[1]);

        $classConfigurations = $this->getConfigurations($this->reader->getClassAnnotations($object));
        $methodConfigurations = $this->getConfigurations($this->reader->getMethodAnnotations($method));

        $configurations = array();
        foreach (array_merge(array_keys($classConfigurations), array_keys($methodConfigurations)) as $key) {
            if (!array_key_exists($key, $classConfigurations)) {
                $configurations[$key] = $methodConfigurations[$key];
            } elseif (!array_key_exists($key, $methodConfigurations)) {
                $configurations[$key] = $classConfigurations[$key];
            } else {
                $configurations[$key] = $methodConfigurations[$key];
            }
        }

        $request = $event->getRequest();
        foreach ($configurations as $key => $configuration) {
            $request->attributes->set($key, $configuration);
        }
    }

    /**
     * @param array $annotations
     * @return array
     */
    private function getConfigurations(array $annotations)
    {
        $configurations = array();

        foreach ($annotations as $configuration) {
            if ($configuration instanceof ConfigurationInterface) {
                if (!isset($configurations['_'.$configuration->getAliasName()])) {
                    $configurations['_'.$configuration->getAliasName()] = $configuration;
                } else {
                    throw new \LogicException(sprintf('Multiple "%s" annotations are not allowed.', $configuration->getAliasName()));
                }
            }
        }

        return $configurations;
    }
}