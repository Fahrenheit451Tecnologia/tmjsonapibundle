<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Expression;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @DI\Service("tm.expression_evaluator.json_api")
 */
class ExpressionEvaluator
{
    const EXPRESSION_REGEX = '/expr\((?P<expression>.+)\)/';

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var array
     */
    private $context = [];

    /**
     * @DI\InjectParams({
     *     "expressionLanguage" = @DI\Inject("tm.expression_language.json_api")
     * })
     *
     * @param ExpressionLanguage $expressionLanguage
     */
    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setContextVariable(string $key, $value) /* : void */
    {
        $this->context[$key] = $value;
    }

    /**
     * @param mixed $expression
     * @param mixed $data
     * @return mixed
     */
    public function evaluate($expression, $data)
    {
        if (is_array($expression)) {
            return $this->evaluateArray($expression, $data);
        }

        if (!is_string($expression)) {
            return $expression;
        }

        $key = $expression;

        if (!array_key_exists($key, $this->cache)) {
            if (!preg_match(self::EXPRESSION_REGEX, $expression, $matches)) {
                $this->cache[$key] = false;
            } else {
                $expression = $matches['expression'];
                $context = $this->context;
                $context['object'] = $data;
                $this->cache[$key] = $this->expressionLanguage->parse($expression, array_keys($context));
            }
        }

        if (false !== $this->cache[$key]) {
            if (!isset($context)) {
                $context = $this->context;
                $context['object'] = $data;
            }
            return $this->expressionLanguage->evaluate($this->cache[$key], $context);
        }

        return $expression;    }

    /**
     * @param array $array
     * @param mixed $data
     * @return array
     */
    private function evaluateArray(array $array, $data)
    {
        $newArray = array();

        foreach ($array as $key => $value) {
            $key   = $this->evaluate($key, $data);
            $value = is_array($value) ? $this->evaluateArray($value, $data) : $this->evaluate($value, $data);
            $newArray[$key] = $value;
        }

        return $newArray;
    }
}