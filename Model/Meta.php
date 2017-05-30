<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

use Assert\Assertion;
use Assert\InvalidArgumentException;

class Meta implements HasToJson
{
    /**
     * @var array|string[]
     */
    private $meta = [];

    /**
     * Can not be instantiated
     * Create link using Link::create()
     *
     * @param array $meta
     */
    public function __construct(array $meta = [])
    {
        if (!empty($meta)) {
            Assertion::allString(array_keys($meta));

            foreach ($meta as $key => $value) {
                $this->addMeta($key, $value);
            }
        }
    }

    /**
     * @param array $meta
     * @return Meta
     */
    public function create(array $meta = [])
    {
        return new static($meta);
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return Meta
     */
    public function addMeta(string $key, $value) : Meta
    {
        if (!is_string($value) && !is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('Meta value must be a string or an array, type %s given', gettype($value)),
                $value,
                251
            );
        }

        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson() : array
    {
        $meta = [];

        foreach ($this->meta as $key => $value) {
            $meta[$key] = $value;
        }

        return $meta;
    }

    /**
     * @param Meta $meta
     * @return Meta
     */
    public function merge(Meta $meta) : Meta
    {
        foreach ($meta->toJson() as $key => $value) {
            $this->addMeta($key, $value);
        }

        return $this;
    }
}