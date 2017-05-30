<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use JMS\DiExtraBundle\Annotation as DI;
use Metadata\Cache\CacheInterface;
use Metadata\ClassHierarchyMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Metadata\MetadataFactory as BaseMetadataFactory;

/**
 * @DI\Service("tm.metadata_factory.json_api")
 */
class MetadataFactory extends BaseMetadataFactory implements JsonApiResourceMetadataFactoryInterface
{
    /**
     * @var array
     */
    protected $resourceTypeToClassMapping = [];

    /**
     * @DI\InjectParams({
     *     "driver" = @DI\Inject("tm.serialization_driver.chain.json_api"),
     *     "debug" = @DI\Inject("%kernel.debug%")
     * })
     *
     * @param DriverInterface $driver
     * @param bool $debug
     */
    public function __construct(DriverInterface $driver, $debug)
    {
        parent::__construct($driver, ClassHierarchyMetadata::class, $debug);
    }

    /**
     * @DI\InjectParams({
     *     "cache" = @DI\Inject("tm.metadata_cache.json_api")
     * })
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        parent::setCache($cache);
    }

    /**
     * @param array $resourceTypeToClassMapping
     */
    public function setResourceTypeToClassMapping(array $resourceTypeToClassMapping)
    {
        $this->resourceTypeToClassMapping = $resourceTypeToClassMapping;
    }

    /**
     * @param string $resourceType
     * @return ClassMetadata|ClassHierarchyMetadata|MergeableClassMetadata|null
     */
    public function getMetadataForResourceType(string $resourceType)
    {
        if (isset($this->resourceTypeToClassMapping[$resourceType])) {
            return $this->getMetadataForClass($this->resourceTypeToClassMapping[$resourceType]);
        }

        return null;
    }
}