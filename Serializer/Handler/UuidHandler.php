<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface as JMSSubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use TM\ResourceBundle\Uuid\Uuid;

/**
 * @DI\Service("tm.serialization_handler.uuid")
 * @DI\Tag("jms_serializer.subscribing_handler")
 */
class UuidHandler implements JMSSubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods() : array
    {
        $methods = [];

        foreach (['json', 'xml'] as $format) {
            $methods[] = [
                'type'      => Uuid::class,
                'method'    => 'serialize',
                'format'    => $format,
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            ];
            $methods[] = [
                'type'      => Uuid::class,
                'method'    => 'unserialize',
                'format'    => $format,
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
            ];
        }

        return $methods;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Uuid $uuid
     * @param array $type
     * @return string
     */
    public function serialize(JsonSerializationVisitor $visitor, Uuid $uuid, array $type)
    {
        return (string) $uuid;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param $data
     * @param array $type
     * @return null|Uuid
     */
    public function unserialize(JsonSerializationVisitor $visitor, $data, array $type)
    {
        if (Uuid::isValid((string) $data)) {
            return Uuid::fromString((string) $data);
        }

        return null;
    }
}