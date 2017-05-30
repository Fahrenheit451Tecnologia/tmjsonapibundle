<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection;

use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

class TMJsonApiExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition(
                'tm.serialization_driver.chain.json_api',
                new Definition(
                    $container->getParameter('jms_serializer.metadata.chain_driver.class'),
                    [[
                        new Reference('tm.serialization_driver.annotation.json_api'),
                    ]]
                )
            )
            ->setPublic(false)
        ;

        $configDir = '%kernel.cache_dir%/json_api';

        $dir = $container->getParameterBag()->resolveValue($configDir);

        if (!file_exists($dir)) {
            if (!$rs = @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
            }
        }

        $container
            ->setDefinition(
                'tm.metadata_cache.json_api.file_cache',
                new Definition(
                    $container->getParameter('jms_serializer.metadata.cache.file_cache.class'),
                    [ $configDir ]
                )
            )
            ->setPublic(false)
        ;

        $container->setAlias('tm.metadata_cache.json_api', 'tm.metadata_cache.json_api.file_cache');

        $container
            ->setDefinition(
                'tm.serialization_naming_strategy.json_api',
                new Definition(
                    SerializedNameAnnotationStrategy::class,
                    [
                        new Definition(
                            CamelCaseNamingStrategy::class,
                            [ '-', true ]
                        )
                    ]
                )
            )
            ->setPublic(false)
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceNotFoundException
     */
    public function prepend(ContainerBuilder $container)
    {
        foreach ([
            'jms_serializer'    => 'JMSSerializerBundle',
            'fos_rest'          => 'FOSRestBundle',
        ] as $extension => $bundle) {
            if (!$container->hasExtension($extension)) {
                throw new ServiceNotFoundException(sprintf('%s must be registered in kernel', $bundle));
            }
        }

        $container->prependExtensionConfig('fos_rest', [
            'body_listener' => [
                'decoders'  => [
                    'json'  => 'tm.decoder.json_api',
                ]
            ],
            'exception'     => true,
        ]);

        $container->prependExtensionConfig('jms_serializer', [
            'property_naming' => [
                'separator' => '-',
            ]
        ]);
    }
}