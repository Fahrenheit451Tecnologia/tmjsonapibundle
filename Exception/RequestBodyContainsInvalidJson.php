<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use TM\JsonApiBundle\Document\ErrorDocument;
use TM\JsonApiBundle\Model\Meta;

class RequestBodyContainsInvalidJson extends AbstractJsonApiException
{
    const CODE  = '';
    const TITLE = '';

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $errors;

    /**
     * @param string $body
     * @param string $errors
     */
    public function __construct(string $body, string $errors)
    {
        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Request body contains invalid json.'."\n".$errors
        );

        $this->body = $body;
        $this->errors = $errors;
    }

    public function toErrorDocument() : ErrorDocument
    {
        $meta = new Meta();
        $meta->addMeta('body', $this->body);

        return parent::toErrorDocument()->merge(ErrorDocument::create([], $meta));
    }
}