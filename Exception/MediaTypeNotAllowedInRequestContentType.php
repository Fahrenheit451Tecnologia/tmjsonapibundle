<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class MediaTypeNotAllowedInRequestContentType extends AbstractJsonApiException
{
    const CODE  = '';
    const TITLE = '';

    /**
     * @param string $contentType
     */
    public function __construct(string $contentType)
    {
        parent::__construct(
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            sprintf(
                'Request "Content-Type" header must not specify any media types, "%s" given',
                $contentType
            )
        );
    }
}