<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
final class Link
{
    /**
     * @var string
     * @Required
     */
    public $name;

    /**
     * @var string
     * @Required
     */
    public $routeName;

    /**
     * @var array
     */
    public $routeParameters = [];

    /**
     * @var bool
     */
    public $absolute = true;
}