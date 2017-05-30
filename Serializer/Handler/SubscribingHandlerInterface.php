<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

interface SubscribingHandlerInterface
{
    /**
     * @return array
     */
    public static function getSubscribingMethods() : array;
}