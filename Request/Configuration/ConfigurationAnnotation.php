<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request\Configuration;

abstract class ConfigurationAnnotation implements ConfigurationInterface
{
    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $k => $v) {
            if (!method_exists($this, $name = 'set'.$k)) {
                throw new \RuntimeException(sprintf(
                    'Unknown key "%s" for annotation "@%s".',
                    $k,
                    get_class($this)
                ));
            }

            $this->$name($v);
        }
    }
}