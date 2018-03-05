<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use TM\JsonApiBundle\Request\JsonApiRequest;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\MetadataFactory;
use TM\JsonApiBundle\Serializer\JsonApiSerializationVisitor;
use TM\JsonApiBundle\Serializer\Serializer;

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
            ->addArgument(new Reference(MetadataFactory::class))
            ->addArgument(new Reference(JsonApiRequest::class))
            ->setClass(JsonApiSerializationVisitor::class)
        ;

        $container->setAlias('serializer', Serializer::class);
        $container->setAlias('fos_rest.serializer', Serializer::class);
    }
}