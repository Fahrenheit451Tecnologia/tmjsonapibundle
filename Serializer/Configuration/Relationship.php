<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration;

use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use TM\JsonApiBundle\Serializer\Generator\RelationshipValueGenerator;

class Relationship
{
    const MAPPING_BELONGS_TO    = 'belongs_to';
    const MAPPING_HAS_MANY      = 'has_many';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $mapping;

    /**
     * @var string
     */
    protected $expression;

    /**
     * @var bool
     */
    protected $includeByDefault = false;

    /**
     * @var Collection|Link[]
     */
    protected $links;

    /**
     * @param string $name
     * @param string $mapping
     * @param string|null $expression
     * @param bool $includeByDefault
     * @param array|Link[] $links
     */
    public function __construct(
        string $name,
        string $mapping,
        string $expression = null,
        bool $includeByDefault = false,
        array $links = []
    ) {
        $this->name = $name;

        Assertion::choice($mapping, [self::MAPPING_BELONGS_TO, self::MAPPING_HAS_MANY]);
        $this->mapping = $mapping;

        $this->expression = $expression;
        $this->includeByDefault = true === $includeByDefault;

        Assertion::allIsInstanceOf($links, Link::class);

        $this->links = new ArrayCollection();

        foreach ($links as $link) {
            if ($this->links->containsKey($link->getName())) {
                throw new \InvalidArgumentException(sprintf(
                    'Relationship "%s" has multiple links named "%s"',
                    $name,
                    $link->getName()
                ));
            }

            $this->links->set($link->getName(), $link);
        }
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Get mapping
     *
     * @return string
     */
    public function getMapping() : string
    {
        return $this->mapping;
    }

    /**
     * Is mapping "has_many"?
     *
     * @return bool
     */
    public function isHasMany() : bool
    {
        return self::MAPPING_HAS_MANY === $this->mapping;
    }

    /**
     * Is mapping "belongs_to"?
     *
     * @return bool
     */
    public function isBelongsTo() : bool
    {
        return self::MAPPING_BELONGS_TO === $this->mapping;
    }

    /**
     * Get expression
     *
     * @return string
     */
    public function getExpression() /* ?: string */
    {
        return $this->expression;
    }

    /**
     * Get value from relationship
     *
     * @param RelationshipValueGenerator $valueGenerator
     * @param $object
     * @return mixed
     */
    public function getValue(RelationshipValueGenerator $valueGenerator, $object)
    {
        return $valueGenerator->generate($object, $this);
    }

    /**
     * Get includeByDefault
     *
     * @param bool|null $includeByDefault
     * @return boolean
     */
    public function includeByDefault(bool $includeByDefault = null) : bool
    {
        if (null !== $includeByDefault) {
            $this->includeByDefault = true === $includeByDefault;
        }

        return $this->includeByDefault;
    }

    /**
     * Get links
     *
     * @return Collection|Link[]
     */
    public function getLinks() : Collection
    {
        return $this->links;
    }

    /**
     * Has links?
     *
     * @return bool
     */
    public function hasLinks() : bool
    {
        return !$this->links->isEmpty();
    }
}