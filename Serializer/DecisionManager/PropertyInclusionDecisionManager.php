<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\DecisionManager;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use TM\JsonApiBundle\Request\JsonApiRequest;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadataInterface;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

class PropertyInclusionDecisionManager
{
    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @param JsonApiRequest $jsonApiRequest
     * @param PropertyNamingStrategyInterface $namingStrategy
     */
    public function __construct(JsonApiRequest $jsonApiRequest, PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->jsonApiRequest = $jsonApiRequest;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @param PropertyMetadata $propertyMetadata
     * @param ClassMetadataInterface $classMetadata
     * @return bool
     */
    public function shouldIncludeProperty(
        PropertyMetadata $propertyMetadata,
        ClassMetadataInterface $classMetadata
    ) : bool {
        if (!$classMetadata->getDocument()) {
            return true;
        }

        if (false === $fields = $this->jsonApiRequest->getFields($classMetadata->getDocument()->getType())) {
            return true;
        }

        return in_array($this->namingStrategy->translateName($propertyMetadata), $fields);
    }

    /**
     * @param Context $context
     * @param Relationship $relationship
     * @param PropertyMetadata $propertyMetadata
     * @return bool
     */
    public function shouldIncludeRelationship(
        Context $context,
        Relationship $relationship,
        PropertyMetadata $propertyMetadata
    ) : bool {
        if ($relationship->includeByDefault()) {
            return true;
        }

        $currentPath = $this->getCurrentPath($context, $propertyMetadata);

        foreach ($this->jsonApiRequest->getInclude() as $include) {
            if (preg_match(sprintf('/^%s(\.|$)/', $include), $currentPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Context $context
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    protected function getCurrentPath(
        Context $context,
        PropertyMetadata $propertyMetadata
    ) : string {
        $currentPath = [ $this->namingStrategy->translateName($propertyMetadata) ];

        foreach ($context->getMetadataStack() as $metadata) {
            if ($metadata instanceof PropertyMetadata) {
                array_unshift($currentPath, $this->namingStrategy->translateName($metadata));
            }
        }

        return implode('.', $currentPath);
    }
}