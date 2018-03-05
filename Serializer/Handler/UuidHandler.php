<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface as JMSSubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use TM\JsonApiBundle\Model\UuidInterface;

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
                'type'      => UuidInterface::class,
                'method'    => 'serialize',
                'format'    => $format,
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            ];
            $methods[] = [
                'type'      => UuidInterface::class,
                'method'    => 'unserialize',
                'format'    => $format,
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
            ];
        }

        return $methods;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param UuidInterface $uuid
     * @param array $type
     * @return string
     */
    public function serialize(JsonSerializationVisitor $visitor, UuidInterface $uuid, array $type)
    {
        return (string) $uuid;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param $data
     * @param array $type
     * @return null|UuidInterface
     */
    public function unserialize(JsonSerializationVisitor $visitor, $data, array $type)
    {
        if (UuidInterface::isValid((string) $data)) {
            return UuidInterface::fromString((string) $data);
        }

        return null;
    }
}