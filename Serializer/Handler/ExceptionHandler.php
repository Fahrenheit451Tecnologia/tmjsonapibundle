<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use FOS\RestBundle\Util\ExceptionValueMap;
use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use TM\JsonApiBundle\Document\ErrorDocument;
use TM\JsonApiBundle\Exception\AbstractJsonApiException;
use TM\JsonApiBundle\Exception\JsonApiSourceException;
use TM\JsonApiBundle\Model\Error;

class ExceptionHandler implements SubscribingHandlerInterface
{
    /**
     * @var ExceptionValueMap
     */
    private $messagesMap;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param array|ExceptionValueMap $messagesMap
     * @param bool $debug
     */
    public function __construct(ExceptionValueMap $messagesMap, bool $debug)
    {
        $this->messagesMap = $messagesMap;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods() : array
    {
        return [
            [
                'type'      => \Exception::class,
                'method'    => 'serializeExceptionToJson',
            ]
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param \Exception $exception
     * @param array $type
     * @return array
     */
    public function serializeExceptionToJson(
        JsonSerializationVisitor $visitor,
        \Exception $exception,
        array $type,
        Context $context
    ) {
        $templateData = $context->attributes->get('template_data');

        if ($templateData->isDefined()) {
            $statusCode = $templateData->get()['status_code'];
        }

        $statusCode = $this->getStatusCode($exception, isset($statusCode) ? $statusCode : null);

        $error = Error::create()
            ->setStatus((string) $statusCode)
            ->setCode($this->getCode($statusCode))
            ->setDetail($this->getExceptionMessage($exception, $statusCode))
        ;

        if ($exception instanceof JsonApiSourceException) {
            $error->setSource($exception->getSource());
        }

        $errorDocument = ErrorDocument::create([$error]);

        if ($exception instanceof AbstractJsonApiException) {
            $errorDocument = $exception->toErrorDocument();
        }

        $json = $errorDocument->toJson();

        if (null === $visitor->getRoot()) {
            $visitor->setRoot(['errors' => $json]);
        }

        return ['errors' => $json];
    }

    /**
     * @param \Exception $exception
     * @param null $statusCode
     * @return int
     */
    private function getStatusCode(\Exception $exception, $statusCode = null) : int
    {
        if (null === $statusCode) {
            if ($exception instanceof HttpExceptionInterface && null !== $exception->getStatusCode()) {
                $statusCode = $exception->getStatusCode();
            } else {
                $statusCode = $exception->getCode();
            }
        }

        if (0 === $statusCode || !is_numeric($statusCode)) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return (int) $statusCode;
    }

    /**
     * @param int $statusCode
     * @return string
     */
    private function getCode(int $statusCode) : string
    {
        $reflectionClass = new \ReflectionClass(Response::class);
        $constants = $reflectionClass->getConstants();

        foreach ($constants as $name => $value) {
            if (!preg_match('/^HTTP_/', $name)) {
                continue;
            }

            if ($value === $statusCode) {
                return preg_replace('/^HTTP_/', '', $name);
            }
        }

        return 'INTERNAL_SERVER_ERROR';
    }

    /**
     * Extracts the exception message.
     *
     * @param \Exception $exception
     * @param int|null   $statusCode
     *
     * @return string
     */
    protected function getExceptionMessage(\Exception $exception, $statusCode = null)
    {
        $showMessage = $this->messagesMap->resolveException($exception);

        if ($showMessage || $this->debug) {
            return $exception->getMessage();
        }

        return array_key_exists($statusCode, Response::$statusTexts) ? Response::$statusTexts[$statusCode] : 'error';
    }
}