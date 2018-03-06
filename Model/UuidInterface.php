<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

interface UuidInterface
{
    /**
     * @param string $uuid
     * @return bool
     */
    public static function isValid(string $uuid);

    /**
     * @param string $uuid
     * @return UuidInterface
     */
    public static function fromString(string $uuid);
}