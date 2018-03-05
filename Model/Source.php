<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

use Assert\InvalidArgumentException;

class Source implements HasToJson
{
    /**
     * @var string
     */
    private $pointer;

    /**
     * @var string
     */
    private $parameter;

    /**
     * @param string|null $pointer
     * @param string|null $parameter
     */
    private function __construct(string $pointer = null, string $parameter = null)
    {
        $this->pointer = $pointer;
        $this->parameter = $parameter;

        if (null === $pointer && null === $parameter) {
            throw new InvalidArgumentException(
                'A pointer or parameter must be given',
                250,
                null,
                ['pointer' => $pointer, 'parameter' => $parameter]
            );
        }

        if (null !== $pointer && null !== $parameter) {
            throw new InvalidArgumentException(
                'A pointer and parameter can not be used at the same time',
                250,
                null,
                ['pointer' => $pointer, 'parameter' => $parameter]
            );
        }
    }

    /**
     * @param string $pointer
     * @return Source
     */
    public static function fromPointer(string $pointer) : Source
    {
        return new static($pointer);
    }

    /**
     * @param string $parameter
     * @return Source
     */
    public static function fromParameter(string $parameter) : Source
    {
        return new static(null, $parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function toJson() : array
    {
        $source = [];

        if (null !== $this->pointer) {
            $source['pointer'] = $this->pointer;
        }

        if (null !== $this->parameter) {
            $source['parameter'] = $this->parameter;
        }

        return $source;
    }
}