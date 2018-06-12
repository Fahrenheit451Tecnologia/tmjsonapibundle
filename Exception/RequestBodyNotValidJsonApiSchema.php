<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use TM\JsonApiBundle\Document\ErrorDocument;
use TM\JsonApiBundle\Model\Error;
use TM\JsonApiBundle\Model\Meta;
use TM\JsonApiBundle\Model\Source;

class RequestBodyNotValidJsonApiSchema extends AbstractJsonApiException
{
    const CODE  = '';
    const TITLE = '';

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $validationErrors;

    /**
     * @param string $body
     * @param array $validationErrors
     */
    public function __construct(string $body, array $validationErrors)
    {
        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Request body is not a valid JSON API document.'
        );

        $this->body = $body;
        $this->validationErrors = $validationErrors;
    }

    /**
     * @return ErrorDocument
     */
    public function toErrorDocument() : ErrorDocument
    {
        $meta = new Meta();
        $meta->addMeta('body', $this->body);

        $errorDocument = ErrorDocument::create([], $meta);

        foreach ($this->validationErrors as $validationError) {
            $errorDocument->addError($this->getErrorObject($validationError));
        }

        return $errorDocument;
    }

    /**
     * @param array $validationError
     * @return Error
     */
    private function getErrorObject(array $validationError) : Error
    {
        $this->checkConstants();

        $error = Error::create()
            ->setStatus((string) $this->getStatusCode())
            ->setCode(static::CODE)
            ->setTitle(static::TITLE)
            ->setDetail(ucfirst($validationError['message']))
        ;

        if (!empty($validationError['property'])) {
            $error->setSource(Source::fromPointer($validationError['property']));
        }

        return $error;
    }
}