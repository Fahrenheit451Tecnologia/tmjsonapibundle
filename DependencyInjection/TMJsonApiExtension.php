<?php declare(strict_types=1);

namespace TM\JsonApiBundle\DependencyInjection;

use JMS\Serializer\Exception\RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $bundles = $container->getParameter('kernel.bundles');

        foreach ($config['metadata']['directories'] as $directory) {
            $directory['path'] = rtrim(str_replace('\\', '/', $directory['path']), '/');

            if ('@' === $directory['path'][0]) {
                $pathParts = explode('/', $directory['path']);
                $bundleName = substr($pathParts[0], 1);

                if (!isset($bundles[$bundleName])) {
                    throw new RuntimeException(sprintf('The bundle "%s" has not been registered with AppKernel. Available bundles: %s', $bundleName, implode(', ', array_keys($bundles))));
                }

                $ref = new \ReflectionClass($bundles[$bundleName]);
                $directory['path'] = dirname($ref->getFileName()) . substr($directory['path'], strlen('@' . $bundleName));
            }

            $dir = rtrim($directory['path'], '\\/');
            if (!file_exists($dir)) {
                throw new RuntimeException(sprintf('The metadata directory "%s" does not exist for the namespace "%s"', $dir, $directory['namespace_prefix']));
            }

            $directories[rtrim($directory['namespace_prefix'], '\\')] = $dir;
        }

        if (empty($directories)) {
            $container
                ->setAlias(
                    'tm.serialization_metadata.file_locator',
                    'jms_serializer.metadata.file_locator'
                )
            ;
        } else {
            $container
                ->setDefinition('tm.serialization_metadata.file_locator', new Definition(
                    \Metadata\Driver\FileLocator::class,
                    [
                        $directories,
                    ]
                ))
            ;
        }
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