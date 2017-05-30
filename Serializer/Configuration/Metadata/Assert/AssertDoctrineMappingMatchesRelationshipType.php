<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata\Assert;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

class AssertDoctrineMappingMatchesRelationshipType
{
    /**
     * @param ManagerRegistry $doctrine
     * @param \ReflectionClass $class
     * @param string $property
     * @param string $mapping
     * @throw \InvalidArgumentException
     */
    public static function assertMatch(
        ManagerRegistry $doctrine,
        \ReflectionClass $class,
        string $property,
        string $mapping
    ) {
        if (null === $manager = $doctrine->getManagerForClass($class->getName())) {
            return;
        }

        /** @var ClassMetadata $classMetadata */
        if (null === $classMetadata = $manager->getClassMetadata($class->getName())) {
            return;
        }

        if (!$classMetadata->hasAssociation($property)) {
            return;
        }

        foreach ($classMetadata->getAssociationMappings() as $key => $value) {
            if ($key !== $property) {
                continue;
            }

            if (Relationship::MAPPING_HAS_MANY === $mapping) {
                if (in_array($value['type'], [
                    ClassMetadataInfo::MANY_TO_MANY,
                    ClassMetadataInfo::ONE_TO_MANY,
                    ClassMetadataInfo::TO_MANY,
                ])) {
                    return;
                }

                throw new \InvalidArgumentException(sprintf(
                    'Relationship "%s" for class "%s" is set to "has_many" but Doctrine recognises it as "belongs_to"',
                    $property,
                    $class->getName()
                ));
            }

            if (Relationship::MAPPING_BELONGS_TO === $mapping) {
                if (in_array($value['type'], [
                    ClassMetadataInfo::ONE_TO_ONE,
                    ClassMetadataInfo::MANY_TO_ONE,
                    ClassMetadataInfo::TO_ONE,
                ])) {
                    return;
                }

                throw new \InvalidArgumentException(sprintf(
                    'Relationship "%s" for class "%s" is set to "belongs_to" but Doctrine recognises it as "has_many"',
                    $property,
                    $class->getName()
                ));
            }

            throw new \InvalidArgumentException(sprintf(
                'Property "%s" in class "%s" must have a valid relationship type. Available types are: "%s"',
                $property,
                $class->getName(),
                implode('", "', Relationship::MAPPING_TYPES)
            ));
        }
    }
}