<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Generator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Serializer\Expression\ExpressionEvaluator;

class RelationshipValueGenerator
{
    /**
     * @var ExpressionEvaluator
     */
    private $expressionLanguage;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param ExpressionEvaluator $expressionEvaluator
     */
    public function __construct(ExpressionEvaluator $expressionEvaluator)
    {
        $this->expressionLanguage = $expressionEvaluator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, Relationship $relationship)
    {
        if (null === $relationship->getExpression()) {
            if (!$this->getPropertyAccessor()->isReadable($data, $relationship->getName())) {
                throw new \InvalidArgumentException(sprintf(
                    'Relationship "%s" is not readable for class "%s"',
                    $relationship->getName(),
                    get_class($data)
                ));
            }

            return $this->getPropertyAccessor()->getValue($data, $relationship->getName());
        }

        return $this->expressionLanguage->evaluate($relationship->getExpression(), $data);
    }

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}