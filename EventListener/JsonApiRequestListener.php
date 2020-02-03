<?php declare(strict_types=1);

namespace TM\JsonApiBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\Request\Configuration\RequestParameters;
use TM\JsonApiBundle\Request\JsonApiRequest;

class JsonApiRequestListener
{
    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param JsonApiRequest $jsonApiRequest
     * @param Reader $reader
     */
    public function __construct(JsonApiRequest $jsonApiRequest, Reader $reader)
    {
        $this->jsonApiRequest = $jsonApiRequest;
        $this->reader = $reader;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->jsonApiRequest->handleRequest($event->getRequest());
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event)
    {
        if (!$this->jsonApiRequest->hasJsonApiContentType()) {
            return;
        }

        $request = $event->getRequest();

        /** @var RequestParameters $configuration */
        if (!$configuration = $request->attributes->get('_request_parameters')) {
            $configuration = new RequestParameters([]);
        }

        if (is_array($configuration)) {
            $configuration = new RequestParameters($configuration);
        }

        $this->assertIdMatch($request, $configuration);

        if ($configuration->includeId()) {
            $request->request->set('id', $this->jsonApiRequest->getId());
        }

        if ($configuration->includeType()) {
            $request->request->set('type', $this->jsonApiRequest->getType());
        }
    }

    /**
     * Make sure id provided in request matches id provided in JSON API content
     *
     * @param Request $request
     * @param RequestParameters $configuration
     */
    private function assertIdMatch(Request $request, RequestParameters $configuration)
    {
        $requestIdField = $configuration->getRequestIdField();

        if (!$request->attributes->has($requestIdField)) {
            return;
        }

        if (!$this->jsonApiRequest->hasId()) {
            return;
        }

        if ($request->attributes->get($requestIdField) === $this->jsonApiRequest->getId()) {
            return;
        }

        throw ExceptionFactory::jsonApiIdAndRequestIdMismatch(
            $this->jsonApiRequest->getId(),
            $request->attributes->get($requestIdField)
        );
    }
}