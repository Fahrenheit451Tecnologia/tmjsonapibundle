<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use JMS\Serializer\Metadata\PropertyMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

class VirtualRelationshipPropertyMetadata extends PropertyMetadata
{
    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @param $class
     * @param Relationship $relationship
     */
    public function __construct(
        $class,
        Relationship $relationship
    ) {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $this->relationship = $relationship;

        $this->class = $class;
        $this->name = $relationship->getName();
    }

    /**
     * {@inheritdoc}
     *
     * @todo
     */
    public function getValue($obj)
    {
        return $this->relationship->getValue();
    }

    /**
     * @return Relationship
     */
    public function getRelationship() : Relationship
    {
        return $this->relationship;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->relationship,
            parent::serialize()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list(
            $this->relationship,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}