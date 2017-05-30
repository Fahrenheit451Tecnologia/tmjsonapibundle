<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration;

class Id
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getField() : string
    {
        return $this->field;
    }
}