<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer;

use FOS\RestBundle\Context\Context as FOSRestContext;
use FOS\RestBundle\Serializer\Serializer as FOSRestSerializerInterface;
use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Serializer as JMSSerializer;
use JMS\Serializer\Context;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use TM\JsonApiBundle\Serializer\DecisionManager\JsonApiSerializationDecisionManager;

class Serializer implements FosRestSerializerInterface
{
    /**
     * @internal
     */
    const SERIALIZATION = 0;

    /**
     * @internal
     */
    const DESERIALIZATION = 1;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var JsonApiSerializationDecisionManager
     */
    private $jsonApiSerializationDecisionManager;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var ExclusionStrategyInterface[]
     */
    protected $exclusionStrategies = [];

    /**
     * @param SerializerInterface $serializer
     * @param JsonApiSerializationDecisionManager $jsonApiSerializationDecisionManager
     * @param PropertyNamingStrategyInterface $namingStrategy
     */
    public function __construct(
        SerializerInterface $serializer,
        JsonApiSerializationDecisionManager $jsonApiSerializationDecisionManager,
        PropertyNamingStrategyInterface $namingStrategy
    ) {
        $this->serializer = $serializer;
        $this->jsonApiSerializationDecisionManager = $jsonApiSerializationDecisionManager;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @param mixed $exclusionStrategies
     */
    public function setExclusionStrategies($exclusionStrategies)
    {
        $this->exclusionStrategies = $exclusionStrategies;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, FOSRestContext $context = null)
    {
        $context = $this->convertContext($context, self::SERIALIZATION, $format);

        if ($this->jsonApiSerializationDecisionManager->serializeToJsonApi($format)) {
            $reflectionProperty = new \ReflectionProperty(JMSSerializer::class, 'serializationVisitors');
            $reflectionProperty->setAccessible(true);

            $serializationVisitor = $reflectionProperty
                ->getValue($this->serializer)
                ->get('json')
                ->getOrThrow(new UnsupportedFormatException(
                    'The format "json" is not supported for serialization.'
                ))
            ;

            $reflectionProperty = new \ReflectionProperty(AbstractVisitor::class, 'namingStrategy');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($serializationVisitor, $this->namingStrategy);
        }

        return $this->serializer->serialize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, FOSRestContext $context)
    {
        $context = $this->convertContext($context, self::DESERIALIZATION, $format);

        return $this->serializer->deserialize($data, $type, $format, $context);
    }

    /**
     * @param FOSRestContext $context
     * @param int            $direction {@see self} constants
     * @return Context
     */
    private function convertContext(FOSRestContext $context, $direction, $format)
    {
        if ($direction === self::SERIALIZATION) {
            $jmsContext = SerializationContext::create();
        } else {
            $jmsContext = DeserializationContext::create();

            $maxDepth = $context->getMaxDepth(false);
            if (null !== $maxDepth) {
                for ($i = 0; $i < $maxDepth; ++$i) {
                    $jmsContext->increaseDepth();
                }
            }
        }

        foreach ($context->getAttributes() as $key => $value) {
            $jmsContext->attributes->set($key, $value);
        }

        if (null !== $context->getVersion()) {
            $jmsContext->setVersion($context->getVersion());
        }
        $groups = $context->getGroups();
        if (!empty($groups)) {
            $jmsContext->setGroups($context->getGroups());
        }
        if (null !== $context->getMaxDepth()) {
            $jmsContext->enableMaxDepthChecks();
        }
        if (null !== $context->getSerializeNull()) {
            $jmsContext->setSerializeNull($context->getSerializeNull());
        }

        if ($format === 'json') {
            foreach ($this->exclusionStrategies as $exclusionStrategy) {
                $jmsContext->addExclusionStrategy($exclusionStrategy);
            }
        }

        return $jmsContext;
    }
}