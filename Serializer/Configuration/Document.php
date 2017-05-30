<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration;

class Document
{
    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        if (null === $type) {
            throw new \RuntimeException('A JSON-API resource must have a type defined and cannot be "null".');
        }

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }
}