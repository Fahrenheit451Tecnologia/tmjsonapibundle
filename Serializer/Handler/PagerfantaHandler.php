<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface as JMSSubscribingHandlerInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use TM\JsonApiBundle\Serializer\JsonApiSerializationVisitor;
use TM\JsonApiBundle\Serializer\Representation\PaginatedRepresentation;

class PagerfantaHandler implements JMSSubscribingHandlerInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $router
     */
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => Pagerfanta::class,
                'method'    => 'serializePagerfantaToJson',
            ),
        );
    }

    /**
     * @param JsonApiSerializationVisitor $visitor
     * @param                             $object
     * @param array                       $type
     * @param Context                     $context
     *
     * @return array
     */
    public function serializePagerfantaToJson(
        JsonApiSerializationVisitor $visitor,
        $object,
        array $type,
        Context $context
    ) {
        $representation = $this->createPaginatedRepresentation($object);

        if (false === $visitor->isJsonApiDocument()) {
            return $context->accept($representation->getItems());
        }

        return $this->transformRoot($representation, $visitor, $context);
    }

    /**
     * Transforms root of visitor with additional data based on the representation.
     *
     * @param PaginatedRepresentation     $representation
     * @param JsonApiSerializationVisitor $visitor
     * @param Context                     $context
     * @return mixed
     */
    protected function transformRoot(
        PaginatedRepresentation $representation,
        JsonApiSerializationVisitor $visitor,
        Context $context
    ) {
        // serialize items
        $data = $context->accept($representation->getItems());

        $root = $visitor->getRoot();

        $root['meta'] = [
            'page'  => $representation->getPage(),
            'limit' => $representation->getLimit(),
            'pages' => $representation->getPages(),
            'total' => $representation->getTotal(),
            'start' => $representation->getStart(),
            'end'   => $representation->getEnd(),
            'count' => $representation->getCount(),
        ];

        $root['links'] = [
            'self'      => $this->getUriForPage(),
            'first'     => $this->getUriForPage(1),
            'last'      => $this->getUriForPage($representation->getPages()),
            'next'      => null,
            'previous'  => null,
        ];

        if ($representation->hasNextPage()) {
            $root['links']['next'] = $this->getUriForPage($representation->getNextPage());
        }

        if ($representation->hasPreviousPage()) {
            $root['links']['previous'] = $this->getUriForPage($representation->getPreviousPage());
        }

        $visitor->setRoot($root);

        return $data;
    }

    /**
     * @param int|null $page
     * @return string
     */
    protected function getUriForPage(int $page = null) : string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $page) {
            $page = $request->query->get('page', 1);
        }

        return $this->router->generate(
            $request->attributes->get('_route'),
            array_merge(
                $request->attributes->get('_route_params'),
                ['page' => $page]
            )
        );
    }

    /**
     * @param Pagerfanta $pagerfanta
     * @return PaginatedRepresentation
     */
    protected function createPaginatedRepresentation(Pagerfanta $pagerfanta) : PaginatedRepresentation
    {
        $items = $pagerfanta->getCurrentPageResults();

        if ($items instanceof \ArrayIterator) {
            $items = $items->getArrayCopy();
        }

        return new PaginatedRepresentation(
            $items,
            $pagerfanta->getCurrentPage(),
            $pagerfanta->getMaxPerPage(),
            $pagerfanta->getNbPages(),
            $pagerfanta->getNbResults()
        );
    }
}