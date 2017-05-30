<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;
use TM\JsonApiBundle\Serializer\Configuration\Document;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

class ClassMetadata extends MergeableClassMetadata implements ClassMetadataInterface
{
    /**
     * @var Document
     */
    protected $document;

    /**
     * @var string
     */
    protected $idField;

    /**
     * @var Collection|Link[]
     */
    protected $links;

    /**
     * @var Collection|Relationship[]
     */
    protected $relationships;

    public function __construct($name)
    {
        parent::__construct($name);

        $this->links = new ArrayCollection();
        $this->relationships = new ArrayCollection();
    }

    /**
     * @return Document
     */
    public function getDocument() /* ?: Document */
    {
        return $this->document;
    }

    /**
     * @param Document $document
     */
    public function setDocument(Document $document) /* : void */
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getIdField() : string
    {
        if (null === $this->idField) {
            return 'id';
        }

        return $this->idField;
    }

    /**
     * @param string $idField
     */
    public function setIdField($idField) /* : void */
    {
        $this->idField = $idField;
    }

    /**
     * @return string
     */
    public function getIdValue($object) : string
    {
        $idField = $this->getIdField();

        $idReflection = $this->reflection->getProperty($idField);
        $idReflection->setAccessible(true);
        $idReflection->getValue($object);

        return (string) $idReflection->getValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks() : Collection
    {
        if (!$this->links instanceof Collection) {
            $links = $this->links;
            
            $this->links = new ArrayCollection();
            
            if (is_array($links)) {
                foreach ($links as $link) {
                    $this->addLink($link);
                }
            }
        }

        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function setLinks(Collection $collection) /* : void */
    {
        $this->links = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function addLink(Link $link) /* : void */
    {
        $this->getLinks()->set($link->getName(), $link);
    }

    /**
     * {@inheritdoc}
     */
    public function hasLink(string $name) : bool
    {
        return $this->getLinks()->containsKey($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getLink(string $name, bool $strict = false) /* ?: Link */
    {
        if ($this->hasLink($name)) {
            return $this->getLinks()->get($name);
        }

        if ($strict) {
            throw new \InvalidArgumentException(sprintf(
                'Link "%s" can not be found',
                $name
            ));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationships() : Collection
    {
        if (!$this->relationships instanceof Collection) {
            $relationships = $this->relationships;
            
            $this->relationships = new ArrayCollection();
            
            if (is_array($relationships)) {
                foreach ($relationships as $relationship) {
                    $this->addRelationship($relationship);
                }
            }
        }

        return $this->relationships;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelationships(Collection $collection) /* : void */
    {
        $this->relationships = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function addRelationship(Relationship $relationship) /* : void */
    {
        $this->getRelationships()->set($relationship->getName(), $relationship);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRelationship(string $name) : bool
    {
        return $this->getRelationships()->containsKey($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationship(string $name, bool $strict = false) /* ?: Relationship */
    {
        if ($this->hasRelationship($name)) {
            return $this->getRelationships()->get($name);
        }

        if ($strict) {
            throw new \InvalidArgumentException(sprintf(
                'Relationship "%s" can not be found',
                $name
            ));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(MergeableInterface $object)
    {
        if (!$object instanceof self) {
            throw new \InvalidArgumentException(sprintf('Object must be an instance of %s.', __CLASS__));
        }

        parent::merge($object);

        $this->document = $object->getDocument();
        $this->idField = $object->getIdField();

        foreach ($object->getRelationships() as $relationship) {
            $this->addRelationship($relationship);
        }

        foreach ($object->getLinks() as $link) {
            $this->addLink($link);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->document,
            $this->idField,
            $this->relationships,
            $this->links,
            parent::serialize(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        // prevent errors if not all key's are set
        @list(
            $this->document,
            $this->idField,
            $relationships,
            $links,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);

        $this->links = new ArrayCollection();

        foreach ($links as $link) {
            $this->addLink($link);
        }

        $this->relationships = new ArrayCollection();

        foreach ($relationships as $relationship) {
            $this->addRelationship($relationship);
        }
    }
}