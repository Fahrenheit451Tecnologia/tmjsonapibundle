<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class InternalServerError extends AbstractJsonApiException
{
    const CODE  = '';
    const TITLE = '';

    /**
     * @param string $details
     */
    public function __construct(string $details)
    {
        parent::__construct(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $details
        );
    }
}