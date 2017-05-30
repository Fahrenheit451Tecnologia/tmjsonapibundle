<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request\Configuration;

/**
 * @Annotation
 */
class RequestParameters extends ConfigurationAnnotation
{
    /**
     * @var bool
     */
    private $includeId = false;

    /**
     * @var bool
     */
    private $includeType = false;

    /**
     * @var string
     */
    private $requestIdField = 'id';

    /**
     * Get includeId
     *
     * @return boolean
     */
    public function includeId()
    {
        return $this->includeId;
    }

    /**
     * Set includeId
     *
     * @param boolean $includeId
     * @return void
     */
    public function setIncludeId(bool $includeId) /* : void */
    {
        $this->includeId = $includeId;
    }

    /**
     * Get includeType
     *
     * @return boolean
     */
    public function includeType()
    {
        return $this->includeType;
    }

    /**
     * Set includeType
     *
     * @param boolean $includeType
     * @return void
     */
    public function setIncludeType(bool $includeType) /* : void */
    {
        $this->includeType = $includeType;
    }

    /**
     * Set requestIdField
     *
     * @return string
     */
    public function getRequestIdField(): string
    {
        return $this->requestIdField;
    }

    /**
     * Get requestIdField
     *
     * @param string $requestIdField
     */
    public function setRequestIdField(string $requestIdField)
    {
        $this->requestIdField = $requestIdField;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName() : string
    {
        return 'request_parameters';
    }
}