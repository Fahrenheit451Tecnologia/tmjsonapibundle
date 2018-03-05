<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class JsonApiIdAndRequestIdMismatch extends AbstractJsonApiException
{
    const CODE  = '';
    const TITLE = '';

    /**
     * @param int|string $jsonApiId
     * @param int|string $requestId
     */
    public function __construct($jsonApiId, $requestId)
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            sprintf(
                'ID in JSON API ("%s") resource and ID in request ("%s") do not match',
                (string) $jsonApiId,
                (string) $requestId
            )
        );
    }
}