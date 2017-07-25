<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer;

use Doctrine\Common\Util\ClassUtils;
use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\ClassMetadata as JMSClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\Accessor\ExpressionAccessorStrategy;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadata as JsonApiClassMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Serializer\Event\JsonEventSubscriber;
use Metadata\MetadataFactoryInterface;

class JsonApiSerializationVisitor extends JsonSerializationVisitor
{
    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var ExpressionAccessorStrategy
     */
    protected $expressionAccessorStrategy;

    /**
     * @var bool
     */
    protected $showVersionInfo;

    /**
     * @var integer
     */
    protected $includeMaxDepth = false;

    private $isJsonApiDocument = false;

    private $includedResources = [];

    /**
     * @param PropertyNamingStrategyInterface $propertyNamingStrategy
     * @param ExpressionAccessorStrategy $expressionAccessorStrategy
     * @param MetadataFactoryInterface $metadataFactory
     * @param bool $showVersionInfo
     * @param null $includeMaxDepth
     */
    public function __construct(
        PropertyNamingStrategyInterface $propertyNamingStrategy,
        ExpressionAccessorStrategy $expressionAccessorStrategy,
        MetadataFactoryInterface $metadataFactory,
        $showVersionInfo = true,
        $includeMaxDepth = null
    ) {
        parent::__construct($propertyNamingStrategy);

        $this->expressionAccessorStrategy = $expressionAccessorStrategy;
        $this->metadataFactory = $metadataFactory;
        $this->showVersionInfo = $showVersionInfo;
        $this->includeMaxDepth = $includeMaxDepth;
    }

    /**
     * @return bool
     */
    public function isJsonApiDocument()
    {
        return $this->isJsonApiDocument;
    }

    /**
     * @param mixed $root
     *
     * @return array
     */
    public function prepare($root)
    {
        if (is_array($root) && array_key_exists('data', $root)) {
            $data = $root['data'];
        } else {
            $data = $root;
        }

        $this->isJsonApiDocument = $this->validateJsonApiDocument($data);

        if ($this->isJsonApiDocument) {
            $meta = null;
            if (is_array($root) && isset($root['meta']) && is_array($root['meta'])) {
                $meta = $root['meta'];
            }

            return $this->buildJsonApiRoot($data, $meta);
        }

        return $root;
    }

    protected function buildJsonApiRoot($data, array $meta = null)
    {
        $root = array(
            'data' => $data,
        );

        if ($meta) {
            $root['meta'] = $meta;
        }

        return $root;
    }

    /**
     * it is a JSON-API document if:
     *  - it is an object and is a JSON-API resource
     *  - it is an array containing objects which are JSON-API resources
     *  - it is empty (we cannot identify it)
     *
     * @param mixed $data
     *
     * @return bool
     */
    protected function validateJsonApiDocument($data)
    {
        if (is_array($data) && count($data) > 0 && !$this->hasDocument($data)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        if (false === $this->isJsonApiDocument) {
            return parent::getResult();
        }

        $root = $this->getRoot();

        // TODO: Error handling
        if (isset($root['data']) && array_key_exists('errors', $root['data'])) {
            $this->setRoot($root['data']);

            return parent::getResult();
        }

        if ($root) {
            $data = array();
            $meta = array();
            $links = array();

            if (isset($root['data'])) {
                $data = $root['data'];
            }

            if (isset($root['meta'])) {
                $meta = $root['meta'];
            }

            if (isset($root['links'])) {
                $links = $root['links'];
            }

            // start building new root array
            $root = array();

            if ($this->showVersionInfo) {
                $root['jsonapi'] = array(
                    'version' => '1.0',
                );
            }

            if ($meta) {
                $root['meta'] = $meta;
            }

            if ($links) {
                $root['links'] = $links;
            }

            $root['data'] = $data;

            if (!empty($this->includedResources)) {
                $root['included'] = array_values($this->includedResources);
            }

            $this->setRoot($root);
        }

        return parent::getResult();
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        /** @var JsonApiClassMetadata $jsonApiMetadata */
        $jsonApiMetadata = $this->metadataFactory->getMetadataForClass($metadata->class);

        if ($jsonApiMetadata && $jsonApiMetadata->hasRelationship($metadata->name)) {
            return null;
        }

        return parent::visitProperty($metadata, $data, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingObject(JMSClassMetadata $metadata, $data, array $type, Context $context)
    {
        /** @var JsonApiClassMetadata $jsonApiMetadata */
        $jsonApiMetadata = $this->metadataFactory->getMetadataForClass(get_class($data));

        $rs = parent::endVisitingObject($metadata, $data, $type, $context);

        if ($rs instanceof \ArrayObject) {
            $rs = [];
            $this->setRoot($rs);

            return $rs;
        }

        if (null === $jsonApiMetadata || null === $jsonApiMetadata->getDocument()) {
            return $rs;
        }

        $idField = $jsonApiMetadata->getIdField();

        $result = [
            'type'          => $jsonApiMetadata->getDocument()->getType(),
            'id'            => $rs[$idField] ?? null,
            'attributes'    => array_filter($rs, function($key) use ($idField, $jsonApiMetadata) {
                switch ($key) {
                    case $idField:
                    case 'relationships':
                    case 'links':
                    case 'meta':
                    case JsonEventSubscriber::EXTRA_DATA_KEY:
                        return false;
                }

                if ($jsonApiMetadata->hasRelationship($key)) {
                    return false;
                }

                return true;
            }, ARRAY_FILTER_USE_KEY)
        ];

        if (isset($rs['relationships'])) {
            $result['relationships'] = $rs['relationships'];
        }

        if (isset($rs['links'])) {
            $result['links'] = $rs['links'];
        }

        if (isset($rs['meta'])) {
            $result['meta'] = $rs['meta'];
        }

        return $result;
    }

    /**
     * @param Context $context
     * @param Relationship $relationship
     * @param object $object
     */
    public function addIncluded(Context $context, Relationship $relationship, $object)
    {
        if (!$relationship->includeByDefault()) {
            return;
        }

        if (!is_object($object)) {
            throw new \InvalidArgumentException(sprintf(
                'Object must be an object, "%s" given',
                gettype($object)
            ));
        }

        /** @var JsonApiClassMetadata $jsonApiMetadata */
        if (null === $jsonApiMetadata = $this->metadataFactory->getMetadataForClass(ClassUtils::getClass($object))) {
            return;
        }

        $key = sprintf(
            '%s-%s',
            $jsonApiMetadata->getDocument()->getType(),
            $jsonApiMetadata->getIdValue($object)
        );

        if (array_key_exists($key, $this->includedResources)) {
            return;
        }

        $relationshipData = $context->accept($object);

        if (!is_array($relationshipData) || !isset($relationshipData['id'])) {
            return;
        }

        $this->includedResources[$key] = $relationshipData;
    }

    /**
     * @param $items
     *
     * @return bool
     */
    protected function hasDocument($items)
    {
        foreach ($items as $item) {
            return $this->isDocument($item);
        }

        return false;
    }

    /**
     * Check if the given variable is a valid JSON-API resource.
     *
     * @param $data
     *
     * @return bool
     */
    protected function isDocument($data)
    {
        if (is_object($data)) {
            /** @var JsonApiClassMetadata $metadata */
            if ($metadata = $this->metadataFactory->getMetadataForClass(get_class($data))) {
                if ($metadata->getDocument()) {
                    return true;
                }
            }
        }

        return false;
    }
}