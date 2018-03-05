<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request;

use TM\JsonApiBundle\Exception\AcceptMustHaveOneInstanceWithoutMediaTypeParameters;
use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\TMJsonApiBundle;

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