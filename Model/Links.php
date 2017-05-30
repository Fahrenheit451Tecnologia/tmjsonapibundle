<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

use Assert\Assertion;

class Links implements HasToJson
{
    /**
     * @var array|Link[]
     */
    private $links = [];

    /**
     * Can not be instantiated
     * Create links using Links::create()
     *
     * @param array $links
     */
    private function __construct(array $links = [])
    {
        if (!empty($links)) {
            Assertion::allString(array_keys($links));
            Assertion::allIsInstanceOf(array_values($links), Link::class);

            $this->links = $links;
        }
    }

    /**
     * @param array $links
     * @return Links
     */
    public function create(array $links = []) : Links
    {
        return new static($links);
    }

    /**
     * @param string $name
     * @param Link $link
     * @return Links
     */
    public function addLink(string $name, Link $link) : Links
    {
        $this->links[$name] = $link;

        return $this;
    }

    /**
     * @return array
     */
    public function getLinks() : array
    {
        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson() : array
    {
        $links = [];

        foreach ($this->links as $name => $link) {
            $links[$name] = $link->toJson();
        }

        return $links;
    }
}