<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Annotation;

use Doctrine\Common\Util\ClassUtils;
use TM\JsonApiBundle\Serializer\Configuration\Relationship as ConfigurationRelationship;

abstract class Relationship
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $includeByDefault = false;

    /**
     * @var string
     */
    public $expression;

    /**
     * @var array<TM\JsonApiBundle\Serializer\Configuration\Annotation\Link>
     */
    public $links = [];

    /**
     * @return string
     */
    public function getMapping()
    {
        if ($this instanceof BelongsTo) {
            return ConfigurationRelationship::MAPPING_BELONGS_TO;
        }

        if ($this instanceof HasMany) {
            return ConfigurationRelationship::MAPPING_HAS_MANY;
        }

        throw new \InvalidArgumentException(sprintf(
            'Relationship annotation class expected to be one of "%s" or "%s", "%s" given',
            BelongsTo::class,
            HasMany::class,
            ClassUtils::getClass($this)
        ));
    }
}