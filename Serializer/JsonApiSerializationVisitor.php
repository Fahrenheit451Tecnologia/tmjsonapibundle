<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer;

use Doctrine\Common\Util\ClassUtils;
use JMS\Serializer\Context;
use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Exception\NotAcceptableException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\ClassMetadata as JMSClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\Accessor\ExpressionAccessorStrategy;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadata as JsonApiClassMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Serializer\Event\JsonEventSubscriber;
use Metadata\MetadataFactoryInterface;

class JsonApiSerializationVisitor extends AbstractVisitor
{
    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

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
     * @var array
     */
    private $dataStack = [];
    /**
     * @var \ArrayObject
     */
    private $data;

    /**
     * @var int
     */
    private $options = JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param PropertyNamingStrategyInterface $propertyNamingStrategy
     * @param MetadataFactoryInterface $metadataFactory
     * @param bool $showVersionInfo
     * @param null $includeMaxDepth
     */
    public function __construct(
        PropertyNamingStrategyInterface $propertyNamingStrategy,
        MetadataFactoryInterface $metadataFactory,
        $showVersionInfo = true,
        int $options = JSON_PRESERVE_ZERO_FRACTION,
        $includeMaxDepth = null
    ) {

        $this->metadataFactory = $metadataFactory;
        $this->showVersionInfo = $showVersionInfo;
        $this->includeMaxDepth = $includeMaxDepth;
        $this->options = $options;
        $this->dataStack = [];
    }

    /**
     * {@inheritdoc}
     */
    public function visitNull($data, array $type)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function visitString(string $data, array $type)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitBoolean(bool $data, array $type)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger(int $data, array $type)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble(float $data, array $type)
    {
        return $data;
    }

    /**
     * @param array $data
     * @param array $type
     *
     * @return array|\ArrayObject
     */
    public function visitArray(array $data, array $type)
    {
        \array_push($this->dataStack, $data);

        $rs = isset($type['params'][1]) ? new \ArrayObject() : [];

        $isList = isset($type['params'][0]) && !isset($type['params'][1]);

        $elType = $this->getElementType($type);
        foreach ($data as $k => $v) {
            try {
                $v = $this->navigator->accept($v, $elType);
            } catch (NotAcceptableException $e) {
                continue;
            }

            if ($isList) {
                $rs[] = $v;
            } else {
                $rs[$k] = $v;
            }
        }

        \array_pop($this->dataStack);
        return $rs;
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
    public function getResult($data)
    {
        if (false === $this->isJsonApiDocument) {
            return $this->getJsonResult($data);
        }

        $root = $this->getRoot();

        // TODO: Error handling
        if (isset($root['data']) && array_key_exists('errors', $root['data'])) {
            $this->setRoot($root['data']);

            return $this->getJsonResult($root['data']);
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

        return $this->getJsonResult($data);
    }

    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        /** @var JsonApiClassMetadata $jsonApiMetadata */
        $jsonApiMetadata = $this->metadataFactory->getMetadataForClass($metadata->class);

        if ($jsonApiMetadata && $jsonApiMetadata->hasRelationship($metadata->name)) {
            return null;
        }

        return $this->visitJMSProperty($metadata, $data, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingObject(JMSClassMetadata $metadata, $data, array $type, Context $context)
    {
        /** @var JsonApiClassMetadata $jsonApiMetadata */
        $jsonApiMetadata = $this->metadataFactory->getMetadataForClass(get_class($data));

        $rs = $this->endVisitingJMSObject($metadata, $data, $type, $context);

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

    public function getJsonResult($data)
    {
        $result = @json_encode($data, $this->options);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $result;

            case JSON_ERROR_UTF8:
                throw new RuntimeException('Your data could not be encoded because it contains invalid UTF8 characters.');

            default:
                throw new RuntimeException(sprintf('An error occurred while encoding your data (error code %d).', json_last_error()));
        }
    }

    public function visitJMSProperty(PropertyMetadata $metadata, $v): void
    {
        try {
            $v = $this->navigator->accept($v, $metadata->type);
        } catch (NotAcceptableException $e) {
            return;
        }

        if (true === $metadata->skipWhenEmpty && ($v instanceof \ArrayObject || \is_array($v)) && 0 === count($v)) {
            return;
        }

        if ($metadata->inline) {
            if (\is_array($v) || ($v instanceof \ArrayObject)) {
                // concatenate the two array-like structures
                // is there anything faster?
                foreach ($v as $key => $value) {
                    $this->data[$key] = $value;
                }
            }
        } else {
            $this->data[$metadata->serializedName] = $v;
        }
    }

    /**
     * @return array|\ArrayObject
     */
    public function endVisitingJMSObject(ClassMetadata $metadata, object $data, array $type)
    {
        $rs = $this->data;
        $this->data = \array_pop($this->dataStack);

        if (true !== $metadata->isList && empty($rs)) {
            return new \ArrayObject();
        }

        return $rs;
    }

    public function startVisitingObject(ClassMetadata $metadata, object $data, array $type): void
    {
        \array_push($this->dataStack, $this->data);
        $this->data = true === $metadata->isMap ? new \ArrayObject() : [];
    }
}