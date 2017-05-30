<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use TM\JsonApiBundle\Serializer\JsonApiSerializationVisitor;

class SerializerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('jms_serializer.json_serialization_visitor')
            ->replaceArgument(0, new Reference('tm.serialization_naming_strategy.json_api'))
            ->addArgument(new Reference('tm.metadata_factory.json_api'))
            ->addArgument(new Reference('tm.request.json_api'))
            ->setClass(JsonApiSerializationVisitor::class)
        ;

        $container->setAlias('serializer', 'tm.serializer.json_api');
        $container->setAlias('fos_rest.serializer', 'tm.serializer.json_api');
    }
}