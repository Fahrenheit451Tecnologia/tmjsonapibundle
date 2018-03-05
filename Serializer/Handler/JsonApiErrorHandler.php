<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface as JMSSubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use TM\JsonApiBundle\Document\ErrorDocument;
use TM\JsonApiBundle\Model\Error;

class JsonApiErrorHandler implements JMSSubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods() : array
    {
        return [
            [
                'type'      => ErrorDocument::class,
                'method'    => 'serializeErrorDocumentToJson',
                'format'    => 'json',
            ], [
                'type'      => Error::class,
                'method'    => 'serializeErrorToJson',
                'format'    => 'json',
            ]
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param ErrorDocument $errorDocument
     * @param array $type
     * @return array
     */
    public function serializeErrorDocumentToJson(
        JsonSerializationVisitor $visitor,
        ErrorDocument $errorDocument,
        array $type
    ) {
        $isRoot = null === $visitor->getRoot();
        $errors = array_map(function(Error $error) {
            return $error->toJson();
        }, $errorDocument->getErrors());

        if ($isRoot) {
            $visitor->setRoot(['errors' => $errors]);
        }

        return $errors;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Error $error
     * @param array $type
     * @return array
     */
    public function serializeErrorToJson(JsonSerializationVisitor $visitor, Error $error, array $type)
    {
        $isRoot = null === $visitor->getRoot();
        $json = $error->toJson();

        if ($isRoot) {
            $visitor->setRoot(['errors' => [$json]]);
        }

        return $json;
    }
}