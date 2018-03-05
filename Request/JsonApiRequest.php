<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JsonApiRequest
{
    /**
     * @var ContentTypeHeaderChecker
     */
    private $contentTypeChecker;

    /**
     * @var AcceptHeaderChecker
     */
    private $acceptChecker;

    /**
     * @var bool
     */
    private $hasJsonApiContentType = false;

    /**
     * @var bool
     */
    private $acceptsJsonApiResponse = false;

    /**
     * @var array|string[]
     */
    private $include = [];

    /**
     * @var array|array[]
     */
    private $fields = [];

    /**
     * @var string|int|null
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var ParameterBag
     */
    private $parameters;

    /**
     * @var ParameterBag
     */
    private $jsonPointerMap;

    /**
     * @param ContentTypeHeaderChecker $contentTypeChecker
     * @param AcceptHeaderChecker $acceptChecker
     */
    public function __construct(
        ContentTypeHeaderChecker $contentTypeChecker,
        AcceptHeaderChecker $acceptChecker
    ) {
        $this->contentTypeChecker = $contentTypeChecker;
        $this->acceptChecker = $acceptChecker;

        $this->parameters = new ParameterBag();
        $this->jsonPointerMap = new ParameterBag();
    }

    /**
     * @param Request $request
     * @return void
     */
    public function handleRequest(Request $request) /* : void */
    {
        $this->hasJsonApiContentType = $this->contentTypeChecker->check($request->headers->get('content-type', ''));
        $this->acceptsJsonApiResponse = $this->acceptChecker->check($request->headers->get('accept', ''));
        $this->include = array_filter(explode(',', $request->query->get('include', '')));
        $this->fields = array_map(function($fields) {
            return array_filter(explode(',', $fields));
        }, (array) $request->query->get('fields', []));
    }

    /**
     * Get hasJsonApiContentType
     *
     * @return boolean
     */
    public function hasJsonApiContentType()
    {
        return $this->hasJsonApiContentType;
    }

    /**
     * Get acceptsJsonApiResponse
     *
     * @return boolean
     */
    public function acceptsJsonApiResponse()
    {
        return $this->acceptsJsonApiResponse;
    }

    /**
     * Get include
     *
     * @return array|string[]
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * Get fields
     *
     * @param string|null $type
     * @return array|array[]|bool
     */
    public function getFields($type = null)
    {
        if (null !== $type) {
            return $this->fields[$type] ?? false;
        }

        return $this->fields;
    }

    /**
     * Get id
     *
     * @return int|null|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Has id?
     *
     * @return bool
     */
    public function hasId()
    {
        return null !== $this->id;
    }

    /**
     * Set id
     *
     * @param int|string $id
     * @return JsonApiRequest
     */
    public function setId($id) : JsonApiRequest
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return JsonApiRequest
     */
    public function setType(string $type) : JsonApiRequest
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Add parameter
     *
     * @param string $key
     * @param mixed $value
     * @return JsonApiRequest
     */
    public function addParameter(string $key, $value) : JsonApiRequest
    {
        $this->parameters->set($key, $value);

        return $this;
    }

    /**
     * Get jsonPointerMap
     *
     * @return ParameterBag
     */
    public function getJsonPointerMap()
    {
        return $this->jsonPointerMap;
    }

    /**
     * Add jsonPointer
     *
     * @param string $key
     * @param string $pointer
     * @return JsonApiRequest
     */
    public function addJsonPointer(string $key, string $pointer) : JsonApiRequest
    {
        $this->jsonPointerMap->set($key, $pointer);

        return $this;
    }
}