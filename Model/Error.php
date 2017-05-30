<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

use Assert\Assertion;

class Error implements HasToJson
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Links
     */
    private $links;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $detail;

    /**
     * @var Source
     */
    private $source;

    /**
     * @var Meta
     */
    private $meta;

    /**
     * Can not be instantiated
     * Create error using Error::create()
     */
    private function __construct()
    {

    }

    /**
     * @return Error
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId() /* : ?string */
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return Error
     */
    public function setId(string $id) : Error
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get links
     *
     * @return Links
     */
    public function getLinks() /* : ?string */
    {
        return $this->links;
    }

    /**
     * Set links
     *
     * @param Links $links
     * @return Error
     */
    public function setLinks(Links $links) : Error
    {
        $this->links = $links;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus() /* : ?string */
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Error
     */
    public function setStatus(string $status) : Error
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode() /* : ?string */
    {
        return $this->code;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Error
     */
    public function setCode(string $code) : Error
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() /* : ?string */
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Error
     */
    public function setTitle(string $title) : Error
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string
     */
    public function getDetail() /* : ?string */
    {
        return $this->detail;
    }

    /**
     * Set detail
     *
     * @param string $detail
     * @return Error
     */
    public function setDetail(string $detail) : Error
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get source
     *
     * @return Source
     */
    public function getSource() /* : ?Source */
    {
        return $this->source;
    }

    /**
     * Set source
     *
     * @param Source $source
     * @return Error
     */
    public function setSource(Source $source) : Error
    {
        $this->source = $source;

        return $this;
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
     * Set meta
     *
     * @param Meta $meta
     * @return Error
     */
    public function setMeta(Meta $meta) : Error
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson() : array
    {
        $error = [];

        foreach ([
            'id',
            'links',
            'status',
            'code',
            'title',
            'detail',
            'source',
            'meta',
        ] as $property) {
            $value = $this->{$property};

            if (is_int($value)) {
                $value = (string) $value;
            }

            if (is_object($value)) {
                Assertion::isInstanceOf($value, HasToJson::class);

                /** @var HasToJson $value */
                $value = $value->toJson();
            }

            if (!empty($value)) {
                $error[$property] = $value;
            }
        }

        return $error;
    }
}