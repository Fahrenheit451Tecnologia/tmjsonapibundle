<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface as JMSSubscribingHandlerInterface;
use TM\JsonApiBundle\Serializer\DecisionManager\JsonApiSerializationDecisionManager;

/**
 * _DI\Service("tm.registry.json_api_serializer_handler")
 */
class HandlerRegistry implements HandlerRegistryInterface
{
    const FORMAT = '_json_api';

    /**
     * @var JsonApiSerializationDecisionManager
     */
    private $jsonApiSerializationDecisionManager;

    /**
     * @var HandlerRegistryInterface
     */
    private $jmsHandlerRegistry;

    /**
     * _DI\InjectParams({
     *     "jsonApiSerializationDecisionManager" = _DI\Inject("tm.decision_manager.json_api_serialization"),
     *     "jmsHandlerRegistry" = _DI\Inject("tm.registry.jms_serializer_handler")
     * })
     *
     * @param JsonApiSerializationDecisionManager $jsonApiSerializationDecisionManager
     * @param HandlerRegistryInterface $jmsHandlerRegistry
     */
    public function __construct(
        JsonApiSerializationDecisionManager $jsonApiSerializationDecisionManager,
        HandlerRegistryInterface $jmsHandlerRegistry
    ) {
        $this->jsonApiSerializationDecisionManager = $jsonApiSerializationDecisionManager;
        $this->jmsHandlerRegistry = $jmsHandlerRegistry;
    }

    /**
     * @param string $typeName
     * @param mixed $handler
     * @return void
     */
    public function registerJsonApiHandler(string $typeName, $handler) /* : void */
    {
        $this->registerHandler(GraphNavigator::DIRECTION_SERIALIZATION, $typeName, self::FORMAT, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function registerHandler($direction, $typeName, $format, $handler)
    {
        $this->jmsHandlerRegistry->registerHandler($direction, $typeName, $format, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function registerSubscribingHandler(JMSSubscribingHandlerInterface $handler)
    {
        $this->registerSubscribingHandler($handler);
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler($direction, $typeName, $format)
    {
        if (GraphNavigator::DIRECTION_SERIALIZATION === $direction &&
            $this->jsonApiSerializationDecisionManager->serializeToJsonApi($format) &&
            (null !== $handler = $this->getHandlerForType($direction, $typeName, self::FORMAT))
        ) {
            return $handler;
        }

        return $this->getHandlerForType($direction, $typeName, $format);
    }

    /**
     * Get handler to type, by traversing parent classes until either type found or reached root
     *
     * @param $direction
     * @param $typeName
     * @param $format
     * @return callable|null
     */
    private function getHandlerForType($direction, $typeName, $format)
    {
        do {
            $handler = $this->jmsHandlerRegistry->getHandler($direction, $typeName, $format);

            if (null !== $handler) {
                return $handler;
            }
        } while ($typeName = get_parent_class($typeName));
    }
}