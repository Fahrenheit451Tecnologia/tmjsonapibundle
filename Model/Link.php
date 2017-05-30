<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

class Link implements HasToJson
{
    /**
     * @var string
     */
    private $href;

    /**
     * @var Meta
     */
    private $meta;

    /**
     * Can not be instantiated
     * Create link using Link::create()
     *
     * @param string $href
     * @param Meta|null $meta
     */
    private function __construct(string $href, Meta $meta = null)
    {
        $this->href = $href;
        $this->meta = $meta;
    }

    /**
     * @param string $href
     * @param Meta|null $meta
     * @return Link
     */
    public function create(string $href, Meta $meta = null) : Link
    {
        return new Link($href, $meta);
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref() /* :?string */
    {
        return $this->href;
    }

    /**
     * Get meta
     *
     * @return Meta
     */
    public function getMeta() /* : ?Meta */
    {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson() : array
    {
        if (null === $this->meta || empty($meta = $this->meta->toJson())) {
            return $this->href;
        }

        return [
            'href' => $this->href,
            'meta' => $meta,
        ];
    }
}