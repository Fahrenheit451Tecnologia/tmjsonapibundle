<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Util;

use Doctrine\Common\Inflector\Inflector;

class StringUtil
{
    /**
     * Dasherize string
     *
     * @param string $string
     *
     * @return string
     */
    public static function dasherize($string)
    {
        return Inflector::pluralize(str_replace('_', '-', Inflector::tableize($string)));
    }

    /**
     * Convert resource name to a route name
     *
     * @param string $string
     *
     * @return string
     */
    public static function resourceNameToResourceRoute($string)
    {
        return 'get_' . Inflector::tableize(Inflector::singularize($string));
    }
}