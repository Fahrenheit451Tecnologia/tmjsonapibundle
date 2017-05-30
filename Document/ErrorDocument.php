<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Document;

use Assert\Assertion;
use TM\JsonApiBundle\Model\Error;
use TM\JsonApiBundle\Model\Meta;

class ErrorDocument extends Document
{
    /**
     * @var array|Error[]
     */
    private $errors = [];

    /**
     * @param array $errors
     * @param Meta|null $meta
     */
    public function __construct(array $errors = [], Meta $meta = null)
    {
        Assertion::allIsInstanceOf($errors, Error::class);

        parent::__construct($meta);
        $this->errors = $errors;
    }

    /**
     * @param Error $error
     * @return ErrorDocument
     */
    public function addError(Error $error) : ErrorDocument
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return array|Error[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @param Meta|null $meta
     * @return ErrorDocument
     */
    public static function create(array $errors = [], Meta $meta = null)
    {
        return new static($errors, $meta);
    }

    /**
     * @param ErrorDocument $errorDocument
     * @return ErrorDocument
     */
    public function merge(ErrorDocument $errorDocument) : ErrorDocument
    {
        foreach ($errorDocument->getErrors() as $error) {
            $this->addError($error);
        }

        if (null !== $errorDocument->getMeta()) {
            $this->getMeta()->merge($errorDocument->getMeta());
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toJson()
    {
        return array_map(function(Error $error) {
            return $error->toJson();
        }, $this->errors);
    }
}