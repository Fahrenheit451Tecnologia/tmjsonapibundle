<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TMJsonApiExtension extends Extension implements PrependExtensionInterface
{
    /* private */ const CONFIG_FILES = [
        'services',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        foreach (self::CONFIG_FILES as $fileName) {
            $loader->load(sprintf('%s.yaml', $fileName));
        }

        $configDir = '%kernel.cache_dir%/json_api';

        $dir = $container->getParameterBag()->resolveValue($configDir);

        if (!file_exists($dir)) {
            if (!$rs = @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
            }
        }

        $container
            ->getDefinition('tm.metadata_cache.json_api.file_cache')
            ->replaceArgument(0, $configDir)
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
                    'json'  => 'TM\JsonApiBundle\Request\JsonApiDecoder',
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