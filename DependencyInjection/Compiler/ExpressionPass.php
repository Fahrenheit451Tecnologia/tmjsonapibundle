<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tm.expression_evaluator.json_api');

        $inject = [
            'container' => 'service_container',
        ];

        foreach ($inject as $parameter => $service) {
            $definition
                ->addMethodCall('setContextVariable', [ $parameter, new Reference($service)])
            ;
        }
    }
}