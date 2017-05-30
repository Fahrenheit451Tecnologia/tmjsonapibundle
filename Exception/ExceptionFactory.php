<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Exception;

use TM\JsonApiBundle\Model\Source;

class ExceptionFactory
{
    /**
     * @param string $contentType
     * @return MediaTypeNotAllowedInRequestContentType
     */
    public static function mediaTypeNotAllowedInRequestContentType(
        string $contentType
    ) : MediaTypeNotAllowedInRequestContentType {
        return new MediaTypeNotAllowedInRequestContentType($contentType);
    }

    /**
     * @param string $accept
     * @return AcceptMustHaveOneInstanceWithoutMediaTypeParameters
     */
    public static function acceptMustHaveOneInstanceWithoutMediaTypeParameters(
        string $accept
    ) : AcceptMustHaveOneInstanceWithoutMediaTypeParameters {
        return new AcceptMustHaveOneInstanceWithoutMediaTypeParameters($accept);
    }

    /**
     * @param string $details
     * @return InternalServerError
     */
    public static function internalServerError(
        string $details
    ) : InternalServerError {
        return new InternalServerError($details);
    }

    /**
     * @param string $body
     * @param string $errors
     * @return RequestBodyContainsInvalidJson
     */
    public static function requestBodyContainsInvalidJson(
        string $body,
        string $errors
    ) : RequestBodyContainsInvalidJson {
        return new RequestBodyContainsInvalidJson($body, $errors);
    }

    /**
     * @param string $body
     * @param array $validationErrors
     * @return RequestBodyNotValidJsonApiSchema
     */
    public static function requestBodyNotValidJsonApiSchema(
        string $body,
        array $validationErrors
    ) : RequestBodyNotValidJsonApiSchema {
        return new RequestBodyNotValidJsonApiSchema($body, $validationErrors);
    }

    /**
     * @param int|string $jsonApiId
     * @param int|string $requestId
     * @return JsonApiIdAndRequestIdMismatch
     */
    public static function jsonApiIdAndRequestIdMismatch(
        $jsonApiId,
        $requestId
    ) : JsonApiIdAndRequestIdMismatch {
        return new JsonApiIdAndRequestIdMismatch($jsonApiId, $requestId);
    }

    /**
     * @param int $statusCode
     * @param string $message
     * @param Source $source
     * @return JsonApiSourceException
     */
    public static function jsonApiSourceException(
        int $statusCode,
        string $message,
        Source $source
    ) : JsonApiSourceException {
        return new JsonApiSourceException($statusCode, $message, $source);
    }
}