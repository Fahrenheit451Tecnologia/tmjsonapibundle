<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TM\JsonApiBundle\Serializer\Handler\HandlerRegistry;
use TM\JsonApiBundle\Serializer\Handler\SubscribingHandlerInterface;

class RegisterHandlersPass implements CompilerPassInterface
{
    const ERROR_TAGGED_HANDLER      = 'tag named "tm.handler.json_api.serializer"';
    const ERROR_SUBSCRIBING_HANDLER = 'method returned from getSubscribingMethods';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerHandlerRegistry($container);

        $definition = $container->getDefinition('jms_serializer.handler_registry');

        // Add tagged handlers
        foreach ($container->findTaggedServiceIds('tm.handler.json_api.serializer') as $id => $tags) {
            foreach ($tags as $attributes) {
                $this->addMethodCall($definition, $id, $attributes, self::ERROR_TAGGED_HANDLER);
            }
        }

        // Add subscribing handlers
        foreach ($container->findTaggedServiceIds('tm.subscribing_handler.json_api.serializer') as $id => $tags) {
            $class = $container->getDefinition($id)->getClass();

            $ref = new \ReflectionClass($class);
            if (!$ref->implementsInterface(SubscribingHandlerInterface::class)) {
                throw new \RuntimeException(sprintf(
                    'The service "%s" must implement the %s.',
                    $id,
                    SubscribingHandlerInterface::class
                ));
            }

            foreach (call_user_func(array($class, 'getSubscribingMethods')) as $methodData) {
                $this->addMethodCall($definition, $id, $methodData, self::ERROR_SUBSCRIBING_HANDLER);
            }
        }
    }

    /**
     * Pretty much taken directly from FOS\RestBundle\DependencyInjection\Compiler\JMSHandlersPass
     *
     * {@inheritdoc}
     */
    public function registerHandlerRegistry(ContainerBuilder $container)
    {
        $handlerRegistry = new Definition(
            HandlerRegistry::class,
            [
                new Reference('tm.decision_manager.json_api_serialization'),
                new Reference('tm.registry.jms_serializer_handler'),
            ]
        );

        $oldRegistry = $container->getDefinition('jms_serializer.handler_registry');
        $oldRegistry->setPublic(false);

        $container->setDefinition('jms_serializer.handler_registry', $handlerRegistry);
        $container->setDefinition('tm.registry.jms_serializer_handler', $oldRegistry);
    }

    /**
     * @param Definition $definition
     * @param string $serviceId
     * @param array $data
     * @param string $errorString
     * @return void
     */
    private function addMethodCall(
        Definition $definition,
        string $serviceId,
        array $data,
        string $errorString
    ) /* : void */ {
        if (!isset($data['type']) || !isset($data['method'])) {
            throw new \RuntimeException(sprintf(
                'Each %s of service "%s" must have both a "type" and "method" attribute.',
                $errorString,
                $serviceId
            ));
        }

        $definition->addMethodCall(
            'registerJsonApiHandler',
            [
                $data['type'],
                [
                    new Reference($serviceId),
                    $data['method']
                ]
            ]
        );
    }
}