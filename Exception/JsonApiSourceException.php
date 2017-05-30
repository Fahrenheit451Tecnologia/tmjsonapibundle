<?php

namespace TM\JsonApiBundle\Exception;

use TM\JsonApiBundle\Model\Source;

class JsonApiSourceException extends \Exception
{
    /**
     * @var Source
     */
    private $source;

    /**
     * @param int $statusCode
     * @param string $message
     * @param Source $source
     */
    public function __construct(
        int $statusCode,
        string $message,
        Source $source
    ) {
        parent::__construct($message, $statusCode);
        $this->source = $source;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }
}