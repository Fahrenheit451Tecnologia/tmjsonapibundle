<?php

namespace TM\JsonApiBundle\Request;

use JMS\DiExtraBundle\Annotation as DI;
use TM\JsonApiBundle\Exception\AcceptMustHaveOneInstanceWithoutMediaTypeParameters;
use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\TMJsonApiBundle;

/**
 * @DI\Service("tm.header_checker.json_api_accept")
 */
class AcceptHeaderChecker
{
    /**
     * @param string $header
     * @return bool
     * @throws AcceptMustHaveOneInstanceWithoutMediaTypeParameters
     */
    public function check(string $header) : bool
    {
        $acceptList = array_unique(array_filter(explode(',', $header)));

        $hasAcceptHeader = false;

        foreach ($acceptList as $acceptItem) {
            $accept = explode(';', $acceptItem);

            if (TMJsonApiBundle::CONTENT_TYPE !== $accept[0]) {
                continue;
            }

            $hasAcceptHeader = true;

            /** Has "Accept" header with no media types */
            if (1 === count($accept)) {
                return true;
            }
        }

        if (!$hasAcceptHeader) {
            return false;
        }

        throw ExceptionFactory::acceptMustHaveOneInstanceWithoutMediaTypeParameters($header);
    }
}