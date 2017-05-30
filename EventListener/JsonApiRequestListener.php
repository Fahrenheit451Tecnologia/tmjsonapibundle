<?php declare(strict_types=1);

namespace TM\JsonApiBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use TM\JsonApiBundle\Exception\ExceptionFactory;
use TM\JsonApiBundle\Request\Configuration\RequestParameters;
use TM\JsonApiBundle\Request\JsonApiRequest;

/**
 * @DI\Service("tm.listener.json_api_request")
 */
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
     * @DI\InjectParams({
     *     "jsonApiRequest" = @DI\Inject("tm.request.json_api"),
     *     "reader" = @DI\Inject("annotation_reader")
     * })
     *
     * @param JsonApiRequest $jsonApiRequest
     * @param Reader $reader
     */
    public function __construct(JsonApiRequest $jsonApiRequest, Reader $reader)
    {
        $this->jsonApiRequest = $jsonApiRequest;
        $this->reader = $reader;
    }

    /**
     * @DI\Observe(KernelEvents::REQUEST, priority=250)
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->jsonApiRequest->handleRequest($event->getRequest());
    }

    /**
     * @DI\Observe(KernelEvents::CONTROLLER)
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$this->jsonApiRequest->hasJsonApiContentType()) {
            return;
        }

        $request = $event->getRequest();

        /** @var RequestParameters $configuration */
        if (!$configuration = $request->attributes->get('_request_parameters')) {
            $configuration = new RequestParameters([]);
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