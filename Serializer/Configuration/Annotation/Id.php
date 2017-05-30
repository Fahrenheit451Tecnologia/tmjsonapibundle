<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Id
{
    /**
     * @var string
     * @Required
     */
    protected $field;
}