<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request\Configuration;

interface ConfigurationInterface
{
    /**
     * Get alias name of configuration annotation
     *
     * @return string
     */
    public function getAliasName() : string;
}