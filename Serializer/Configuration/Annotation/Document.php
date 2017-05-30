<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Document
{
    /**
     * @var string
     */
    public $type;
}