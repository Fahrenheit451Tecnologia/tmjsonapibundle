<?php

namespace TM\JsonApiBundle\Request;

use JMS\DiExtraBundle\Annotation as DI;
use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\Exception\MediaTypeNotAllowedInRequestContentType;
use TM\JsonApiBundle\TMJsonApiBundle;

/**
 * @DI\Service("tm.header_checker.json_api_content_type")
 */
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