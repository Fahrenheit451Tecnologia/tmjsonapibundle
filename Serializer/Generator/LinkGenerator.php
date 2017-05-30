<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Generator;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Expression\ExpressionEvaluator;

/**
 * @DI\Service("tm.generator.serialization_link")
 */
class LinkGenerator
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var ExpressionEvaluator
     */
    private $expressionLanguage;

    /**
     * @DI\InjectParams({
     *     "router" = @DI\Inject("router"),
     *     "expressionEvaluator" = @DI\Inject("tm.expression_evaluator.json_api")
     * })
     *
     * @param UrlGeneratorInterface $router
     * @param ExpressionEvaluator $expressionEvaluator
     */
    public function __construct(UrlGeneratorInterface $router, ExpressionEvaluator $expressionEvaluator)
    {
        $this->router = $router;
        $this->expressionLanguage = $expressionEvaluator;
    }

    /**
     * Generate URL from data and link object
     *
     * @param mixed $data
     * @param Link $link
     * @return string
     */
    public function generate($data, Link $link) : string
    {
        return $this->router->generate(
            $link->getRouteName(),
            $this->expressionLanguage->evaluate($link->getRouteParameters(), $data),
            $link->isAbsolute() ? UrlGeneratorInterface::ABSOLUTE_URL : null
        );
    }
}