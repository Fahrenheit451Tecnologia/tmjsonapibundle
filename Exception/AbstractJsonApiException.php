<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Assert\Assertion;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TM\JsonApiBundle\Document\ErrorDocument;
use TM\JsonApiBundle\Model\Error;

abstract class AbstractJsonApiException extends HttpException
{
    const CODE      = '_OVERRIDE_';
    const TITLE     = '_OVERRIDE_';

    /**
     * @return ErrorDocument
     */
    public function toErrorDocument() : ErrorDocument
    {
        $this->checkConstants();

        $error = Error::create()
            ->setStatus($this->getStatusCode())
            ->setCode(static::CODE)
            ->setTitle(static::TITLE)
            ->setDetail($this->getMessage())
        ;

        return ErrorDocument::create([ $error ]);
    }

    /**
     * Make sure both CODE and TITLE constants have been overridden in child class
     *
     * @return void
     */
    protected function checkConstants() /* : void */
    {
        Assertion::allNotEq([static::CODE, static::TITLE], '_OVERRIDE_');
    }
}