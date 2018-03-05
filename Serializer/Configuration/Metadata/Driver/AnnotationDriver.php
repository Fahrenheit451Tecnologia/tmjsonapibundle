<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
use TM\JsonApiBundle\Serializer\Configuration\Annotation;
use TM\JsonApiBundle\Serializer\Configuration\Document;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Util\StringUtil;

class AnnotationDriver implements DriverInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $annotations = $this->reader->getClassAnnotations($class);

        if (0 === count($annotations)) {
            return null;
        }

        $classMetadata = new ClassMetadata($class->getName());
        $classMetadata->fileResources[] = $class->getFileName();

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Annotation\Document) {
                // auto transform type from class name
                if (!$annotation->type) {
                    $annotation->type = StringUtil::dasherize($class->getShortName());
                }

                $classMetadata->setDocument(new Document($annotation->type));
            } elseif ($annotation instanceof Annotation\Relationship) {
                foreach (['name', 'expression'] as $property) {
                    if (null === $annotation->{$property}) {
                        throw new \InvalidArgumentException(sprintf(
                            'A %s must be set for @Link annotation when attached to a class',
                            $property
                        ));
                    }
                }

                $this->addRelationship($classMetadata, $annotation);
            } elseif ($annotation instanceof Annotation\Link) {
                $classMetadata->addLink($this->createLink($annotation));
            }
        }

        $classProperties = $class->getProperties();

        foreach ($classProperties as $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Annotation\Id) {
                    $classMetadata->setIdField($property->getName());
                } elseif ($annotation instanceof Annotation\Relationship) {
                    $this->addRelationship(
                        $classMetadata,
                        $annotation,
                        $property->getName()
                    );
                }
            }
        }

        return $classMetadata;
    }

    /**
     * @param Annotation\Link $annotation
     * @return Link
     * @throws \InvalidArgumentException
     */
    private function createLink(Annotation\Link $annotation) : Link
    {
        return new Link(
            $annotation->name,
            $annotation->routeName,
            $annotation->routeParameters,
            $annotation->absolute
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param Annotation\Relationship $annotation
     * @param string|null $propertyName
     * @return void
     */
    private function addRelationship(
        ClassMetadata $classMetadata,
        Annotation\Relationship $annotation,
        string $propertyName = null
    ) /* : void */ {
        $links = [];

        /** @var Annotation\Link $link */
        foreach ($annotation->links as $link) {
            $links[] = $this->createLink($link);
        }

        if (null === $annotation->name && null === $propertyName) {
            throw new \InvalidArgumentException('An name or propertyName must be provided');
        }

        $classMetadata->addRelationship(new Relationship(
            $annotation->name ?: $propertyName,
            $annotation->getMapping(),
            $annotation->expression,
            $annotation->includeByDefault,
            $links
        ));
    }
}