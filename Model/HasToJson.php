<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Model;

interface HasToJson
{
    /**
     * @return array
     */
    public function toJson() : array;
}