<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Document;

use TM\JsonApiBundle\Model\Meta;

abstract class Document
{
    /**
     * @var null|Meta
     */
    private $meta;

    /**
     * @param Meta|null $meta
     */
    public function __construct(Meta $meta = null)
    {
        $this->meta = $meta ?: new Meta();
    }

    /**
     * @param Meta $meta
     * @return $this
     */
    public function setMeta(Meta $meta) /* : Document */
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return Meta
     */
    public function getMeta() : Meta
    {
        return $this->meta;
    }
}