<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use Metadata\ClassHierarchyMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Metadata\MetadataFactory as BaseMetadataFactory;

class MetadataFactory extends BaseMetadataFactory implements JsonApiResourceMetadataFactoryInterface
{
    /**
     * @var array
     */
    protected $resourceTypeToClassMapping = [];

    /**
     * @param DriverInterface $driver
     * @param bool $debug
     */
    public function __construct(DriverInterface $driver, $debug)
    {
        parent::__construct($driver, ClassHierarchyMetadata::class, $debug);
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