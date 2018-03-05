<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Generator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Expression\ExpressionEvaluator;

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