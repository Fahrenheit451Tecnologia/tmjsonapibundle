<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Event;

use Doctrine\Common\Util\ClassUtils;
use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\ClassMetadata as JMSClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadataInterface as JsonApiClassMetadataInterface;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\JsonApiResourceMetadataFactoryInterface;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\VirtualRelationshipPropertyMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Serializer\DecisionManager\PropertyInclusionDecisionManager;
use TM\JsonApiBundle\Serializer\Generator\LinkGenerator;
use TM\JsonApiBundle\Serializer\Generator\RelationshipValueGenerator;
use TM\JsonApiBundle\Serializer\JsonApiSerializationVisitor;
use Metadata\MetadataFactoryInterface;
use RuntimeException;
use Traversable;

class JsonEventSubscriber implements EventSubscriberInterface
{
    const EXTRA_DATA_KEY = '__DATA__';

    /**
     * @var MetadataFactoryInterface
     */
    protected $jsonApiMetadataFactory;

    /**
     * @var MetadataFactoryInterface
     */
    protected $jmsMetadataFactory;

    /**
     * @var PropertyNamingStrategyInterface
     */
    protected $namingStrategy;

    /**
     * @var RelationshipValueGenerator
     */
    protected $relationshipValueGenerator;

    /**
     * @var LinkGenerator
     */
    protected $linkGenerator;

    /**
     * @var PropertyInclusionDecisionManager
     */
    protected $propertyInclusionDecisionManager;

    /**
     * @param JsonApiResourceMetadataFactoryInterface $jsonApiMetadataFactory
     * @param MetadataFactoryInterface $jmsMetadataFactory
     * @param PropertyNamingStrategyInterface $namingStrategy
     * @param LinkGenerator $linkGenerator
     * @param PropertyInclusionDecisionManager $propertyInclusionDecisionManager
     */
    public function __construct(
        JsonApiResourceMetadataFactoryInterface $jsonApiMetadataFactory,
        MetadataFactoryInterface $jmsMetadataFactory,
        PropertyNamingStrategyInterface $namingStrategy,
        RelationshipValueGenerator $relationshipValueGenerator,
        LinkGenerator $linkGenerator,
        PropertyInclusionDecisionManager $propertyInclusionDecisionManager
    ) {
        $this->jsonApiMetadataFactory = $jsonApiMetadataFactory;
        $this->jmsMetadataFactory = $jmsMetadataFactory;
        $this->namingStrategy = $namingStrategy;
        $this->relationshipValueGenerator = $relationshipValueGenerator;
        $this->linkGenerator = $linkGenerator;
        $this->propertyInclusionDecisionManager = $propertyInclusionDecisionManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event'     => Events::PRE_SERIALIZE,
                'format'    => 'json',
                'method'    => 'onPreSerialize',
            ),
            array(
                'event'     => Events::POST_SERIALIZE,
                'format'    => 'json',
                'method'    => 'onPostSerialize',
            ),
        );
    }

    public function onPreSerialize(ObjectEvent $event)
    {
        $context = $event->getContext();
        $visitor = $event->getVisitor();
        $object = $event->getObject();

        if (null === $jsonApiClassMetadata = $this->getJsonApiClassMetadata($object)) {
            return;
        }

        if (!$visitor instanceof JsonApiSerializationVisitor) {
            return;
        }

        $jmsMetadata = $this->getJMSClassMetadata($object);

        /** @var Relationship $relationship */
        foreach ($jsonApiClassMetadata->getRelationships() as $relationship) {
            if (!isset($jmsMetadata->propertyMetadata[$relationship->getName()])) {
                $jmsMetadata->addPropertyMetadata(new VirtualRelationshipPropertyMetadata($object, $relationship));
            }

            $propertyMetadata = $jmsMetadata->propertyMetadata[$relationship->getName()];

            if ($this->propertyInclusionDecisionManager
                ->shouldIncludeRelationship($context, $relationship, $propertyMetadata)
            ) {
                $relationship->includeByDefault(true);
            }
        }

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($jmsMetadata->propertyMetadata as $propertyMetadata) {
            if (!$this->propertyInclusionDecisionManager
                ->shouldIncludeProperty($propertyMetadata, $jsonApiClassMetadata)
            ) {
                unset($jmsMetadata->propertyMetadata[$propertyMetadata->name]);
            }

            if (!$jsonApiClassMetadata->hasRelationship($propertyMetadata->name)) {
                continue;
            }

        //    $relationship = $jsonApiClassMetadata->getRelationship($propertyMetadata->name);

        //    if (!$relationship->hasValueGenerated()) {
        //        $relationship->generateValue($this->relationshipValueGenerator, $object);
        //    }
        }
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $visitor = $event->getVisitor();
        $object = $event->getObject();
        $context = $event->getContext();

        if (null === $jsonApiClassMetadata = $this->getJsonApiClassMetadata($object)) {
            return;
        }

        if (!$visitor instanceof  JsonApiSerializationVisitor) {
            return;
        }

        $jmsMetadata = $this->getJMSClassMetadata($object);

        $visitor->setData(self::EXTRA_DATA_KEY, $this->getRelationshipDataArray($jsonApiClassMetadata, $object));

        $relationships = [];

        /** @var Relationship $relationship */
        foreach ($jsonApiClassMetadata->getRelationships() as $relationship) {
            $relationshipPropertyName = $relationship->getName();

            if (!isset($jmsMetadata->propertyMetadata[$relationshipPropertyName])) {
                continue;
            }

            $jmsPropertyMetadata = $jmsMetadata->propertyMetadata[$relationshipPropertyName];
            $relationshipPayloadKey = $this->namingStrategy->translateName($jmsPropertyMetadata);

            $relationshipData =& $relationships[$relationshipPayloadKey];
            $relationshipData = [];

            if ($relationship->hasLinks()) {
                $relationshipData['links'] = $this->processRelationshipLinks($object, $relationship);
            }

            $relationshipValue = $relationship->getValue($this->relationshipValueGenerator, $object);

            if ($this->isIteratable($relationshipValue) || $relationship->isHasMany()) {
                $relationshipData['data'] = [];

                foreach ($relationshipValue as $item) {
                    $this->processRelationship(
                        $visitor,
                        $context,
                        $relationship,
                        $item,
                        $relationshipData['data'][]
                    );
                }
            } else {
                 $this->processRelationship(
                     $visitor,
                     $context,
                     $relationship,
                     $relationshipValue,
                     $relationshipData['data']
                 );
            }
        }

        if ($relationships) {
            $visitor->setData('relationships', $relationships);
        }

        if (null !== $link = $jsonApiClassMetadata->getLink(Link::NAME_SELF)) {
            $visitor->setData('links', [ Link::NAME_SELF => $this->linkGenerator->generate($object, $link) ]);
        }

        $root = (array)$visitor->getRoot();

        $visitor->setRoot($root);
    }

    /**
     * @param mixed $primaryObject
     * @param Relationship $relationship
     * @return array
     */
    protected function processRelationshipLinks($primaryObject, Relationship $relationship) : array
    {
        $links = [];

        foreach ($relationship->getLinks() as $link) {
            $links[] = $this->linkGenerator->generate($primaryObject, $link);
        }

        return $links;
    }

    /**
     * @param JsonApiSerializationVisitor $visitor
     * @param Context $context
     * @param Relationship $relationship
     * @param object $object
     * @param $relationshipData
     * @return null
     */
    protected function processRelationship(
        JsonApiSerializationVisitor $visitor,
        Context $context,
        Relationship $relationship,
        $object,
        &$relationshipData
    ) /* : null */ {
        if (null === $object) {
            return;
        }

        if (null === $jsonApiRelationClassMetadata = $this->getJsonApiClassMetadata($object)) {
            throw new RuntimeException(sprintf(
                'Metadata for class %s not found. Did you define it as a JSON-API resource?',
                ClassUtils::getClass($object)
            ));
        }

        if (null === $relationshipData = $this->getRelationshipDataArray($jsonApiRelationClassMetadata, $object)) {
            return;
        }

        $visitor->addIncluded($context, $relationship, $object);
    }

    /**
     * @param JsonApiClassMetadataInterface $jsonApiClassMetadata
     * @param mixed $object
     * @return array|null
     */
    protected function getRelationshipDataArray(
        JsonApiClassMetadataInterface $jsonApiClassMetadata,
        $object
    ) /* ?: array */ {
        if (null === $jsonApiClassMetadata->getDocument()) {
            return null;
        }

        return [
            'type'  => $jsonApiClassMetadata->getDocument()->getType(),
            'id'    => $jsonApiClassMetadata->getIdValue($object)
        ];
    }

    /**
     * Checks if an object is really empty, also if it is iteratable and has zero items.
     *
     * @param $object
     *
     * @return bool
     */
    protected function isEmpty($object)
    {
        return empty($object) || ($this->isIteratable($object) && count($object) === 0);
    }

    /**
     * @param $data
     *
     * @return bool
     */
    protected function isIteratable($data)
    {
        return (is_array($data) || $data instanceof Traversable);
    }

    /**
     * @param string|object $className
     * @return \Metadata\ClassMetadata|JsonApiClassMetadataInterface|null
     */
    protected function getJsonApiClassMetadata($className)
    {
        /** @var JsonApiClassMetadataInterface $jsonApiClassMetadata */
        $jsonApiClassMetadata = $this->getClassMetadataFromFactory($className, $this->jsonApiMetadataFactory);

        if (null === $jsonApiClassMetadata) {
            return null;
        }

        return $jsonApiClassMetadata;
    }

    /**
     * @param string|object $className
     * @return \Metadata\ClassMetadata|JMSClassMetadata|null
     */
    protected function getJMSClassMetadata($className)
    {
        return $this->getClassMetadataFromFactory($className, $this->jmsMetadataFactory);
    }

    /**
     * @param string|object $className
     * @return \Metadata\ClassMetadata|null
     */
    protected function getClassMetadataFromFactory($className, MetadataFactoryInterface $factory)
    {
        if (!is_string($className) && !is_object($className)) {
            throw new \InvalidArgumentException(sprintf(
                'Class name must be either a string or an object, "%s" given',
                gettype($className)
            ));
        }

        if (!is_string($className)) {
            /** @var object|string $className */
            $className = ClassUtils::getClass($className);
        }

        return $factory->getMetadataForClass($className);
    }
}