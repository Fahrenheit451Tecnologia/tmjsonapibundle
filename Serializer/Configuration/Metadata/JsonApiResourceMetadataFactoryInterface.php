<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use Metadata\MetadataFactoryInterface;

interface JsonApiResourceMetadataFactoryInterface extends MetadataFactoryInterface
{
    /**
     * @param string $resourceType
     * @return mixed
     */
    public function getMetadataForResourceType(string $resourceType);

    /**
     * @param string $className
     * @return ClassMetadataInterface|null
     */
    public function getMetadataForClass(string $className);
}