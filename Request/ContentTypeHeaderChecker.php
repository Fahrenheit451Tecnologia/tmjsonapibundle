<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request;

use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\Exception\MediaTypeNotAllowedInRequestContentType;
use TM\JsonApiBundle\TMJsonApiBundle;

class ContentTypeHeaderChecker
{
    /**
     * @param string $header
     * @return bool
     * @throws MediaTypeNotAllowedInRequestContentType
     */
    public function check(string $header) : bool
    {
        $lowerCaseHeader = mb_strtolower($header);
        $contentType = explode(';', $lowerCaseHeader);

        if (TMJsonApiBundle::CONTENT_TYPE !== $contentType[0]) {
            return false;
        }

        if (1 === count($contentType)) {
            return true;
        }

        throw ExceptionFactory::mediaTypeNotAllowedInRequestContentType($header);
    }
}