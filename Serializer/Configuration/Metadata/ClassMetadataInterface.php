<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata;

use Doctrine\Common\Collections\Collection;
use TM\JsonApiBundle\Serializer\Configuration\Document;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

interface ClassMetadataInterface
{
    /**
     * @return Document
     */
    public function getDocument() /* ?: Document */;

    /**
     * @param Document $document
     */
    public function setDocument(Document $document) /* : void */;

    /**
     * @param mixed $object
     * @return string
     */
    public function getIdValue($object) : string;

    /**
     * @return string
     */
    public function getIdField() : string;

    /**
     * @param string $idField
     */
    public function setIdField($idField) /* : void */;

    /**
     * @return Collection|Link[]
     */
    public function getLinks() : Collection;

    /**
     * @param Collection|Link[] $collection
     */
    public function setLinks(Collection $collection) /* : void */;

    /**
     * @param Link $link
     */
    public function addLink(Link $link) /* : void */;

    /**
     * @param string
     * @return bool
     */
    public function hasLink(string $name) : bool;

    /**
     * @param string $name
     * @param bool $strict
     * @return Link
     */
    public function getLink(string $name, bool $strict = false) /* ?: Link */;

    /**
     * @return Collection|Relationship[]
     */
    public function getRelationships() : Collection;

    /**
     * @param Collection|Relationship[] $collection
     */
    public function setRelationships(Collection $collection) /* : void */;

    /**
     * @param Relationship $relationship
     */
    public function addRelationship(Relationship $relationship) /* : void */;

    /**
     * @param string
     * @return bool
     */
    public function hasRelationship(string $name) : bool;

    /**
     * @param string $name
     * @param bool $strict
     * @return Relationship
     */
    public function getRelationship(string $name, bool $strict = false) /* ?: Relationship */;
}