<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\DecisionManager;

use TM\JsonApiBundle\Request\JsonApiRequest;

class JsonApiSerializationDecisionManager
{
    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * @var bool|null
     */
    private $forceDecision;

    /**
     * @param JsonApiRequest $jsonApiRequest
     */
    public function __construct(JsonApiRequest $jsonApiRequest)
    {
        $this->jsonApiRequest = $jsonApiRequest;
    }

    /**
     * @param bool $forceDecision
     */
    public function setForceDecision(bool $forceDecision) /* : void */
    {
        $this->forceDecision = $forceDecision;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function serializeToJsonApi(string $format) : bool
    {
        if ('json' !== $format) {
            return false;
        }

        if (null !== $this->forceDecision) {
            return $this->forceDecision;
        }

        return $this->jsonApiRequest->acceptsJsonApiResponse();
    }
}