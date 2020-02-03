<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration\Metadata\Driver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\FileLocatorInterface;
use Symfony\Component\Yaml\Yaml;
use TM\JsonApiBundle\Exception\UnexpectedTypeException;
use TM\JsonApiBundle\Serializer\Configuration\Document;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\Assert\AssertDoctrineMappingMatchesRelationshipType;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadata;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;
use TM\JsonApiBundle\Util\StringUtil;

class YamlDriver extends AbstractFileDriver
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param FileLocatorInterface $locator
     */
    public function __construct(FileLocatorInterface $locator, ManagerRegistry $doctrine)
    {
        parent::__construct($locator);

        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, string $file): ?\Metadata\ClassMetadata
    {
        $config = Yaml::parse(file_get_contents($file));

        if (!isset($config[$name = $class->getName()])) {
            throw new \RuntimeException(sprintf('Expected metadata for class %s to be defined in %s.', $name, $file));
        }

        $config = $config[$name];

        if (!isset($config['json_api'])) {
            return null;
        }

        $classMetadata = new ClassMetadata($name);
        $classMetadata->fileResources[] = $file;
        $classMetadata->fileResources[] = $name;

        if (is_string($config['json_api'])) {
            $config['json_api'] = [
                'type'  => $config['json_api']
            ];
        }

        if (!is_array($config['json_api'])) {
            throw new UnexpectedTypeException($config['json_api'], 'array');
        }

        if (!isset($config['json_api']['type'])) {
            $config['json_api']['type'] = StringUtil::dasherize($class->getShortName());
        }

        $classMetadata->setDocument(new Document($config['json_api']['type']));

        if (isset($config['json_api']['id_field'])) {
            $classMetadata->setIdField($config['json_api']['id_field']);
        }

        if (isset($config['links'])) {
            if (!is_array($config['links'])) {
                throw new UnexpectedTypeException($config['links'], 'array');
            }

            foreach ($config['links'] as $linkConfig) {
                if (!is_array($linkConfig)) {
                    throw new UnexpectedTypeException($linkConfig, 'array');
                }

                $classMetadata->addLink($this->createLink($linkConfig));
            }
        }

        if (isset($config['relationships'])) {
            if (!is_array($config['relationships'])) {
                throw new UnexpectedTypeException($config['relationships'], 'array');
            }

            foreach ($config['relationships'] as $relationshipConfig) {
                if (!isset($relationshipConfig['expression'])) {
                    throw new \InvalidArgumentException(
                        'A expression must be set for relationship when attached to a class'
                    );
                }

                $this->addRelationship($classMetadata, $relationshipConfig);
            }
        }

        if (isset($config['properties'])) {
            foreach ($config['properties'] as $name => $propertyConfig) {
                if (isset($propertyConfig['relationship'])) {
                    if (!is_array($propertyConfig['relationship'])) {
                        throw new UnexpectedTypeException($propertyConfig['relationship'], 'array');
                    }

                    $this->addRelationship($classMetadata, $propertyConfig['relationship'], $name);
                }
            }
        }

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension(): string
    {
        return 'yml';
    }

    /**
     * @param array $config
     * @return Link
     * @throws \InvalidArgumentException
     */
    private function createLink(array $config) : Link
    {
        $config['route_parameters'] = $config['route_parameters'] ?? [];

        if (!is_array($config['route_parameters'])) {
            throw new UnexpectedTypeException($config['route_parameters'], 'array');
        }

        foreach (['name', 'route_name'] as $property) {
            if (!isset($config[$property])) {
                throw new \InvalidArgumentException(sprintf(
                    'Link config must contain a %s property',
                    $property
                ));
            }
        }

        return new Link(
            $config['name'],
            $config['route_name'],
            $config['route_parameters'],
            (bool) ($config['absolute'] ?? false)
        );
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array $config
     * @param string|null $propertyName
     * @return void
     */
    private function addRelationship(
        ClassMetadata $classMetadata,
        array $config,
        string $propertyName = null
    ) /* : void */ {
        if (!isset($config['name']) && null === $propertyName) {
            throw new \InvalidArgumentException('An name or propertyName must be provided');
        }

        $config['name'] = $config['name'] ?? $propertyName;

        if (!isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf(
                'A relationship type must be specified for property "%s" in class "%s"',
                $config['name'],
                $classMetadata->name
            ));
        }

        $mappingTypes = [
            Relationship::MAPPING_BELONGS_TO,
            Relationship::MAPPING_HAS_MANY,
        ];

        if (!in_array($config['type'], $mappingTypes)) {
            throw new \InvalidArgumentException(sprintf(
                'Property "%s" in class "%s" must have a valid relationship type. Available types are: "%s"',
                $config['name'] ?? $propertyName,
                $classMetadata->name,
                implode('", "', $mappingTypes)
            ));
        }

        $links = [];

        if (isset($config['links'])) {
            if (!is_array($config['links'])) {
                throw new UnexpectedTypeException($config['links'], 'array');
            }

            foreach ($config['links'] as $link) {
                $links[] = $this->createLink($link);
            }
        }

        AssertDoctrineMappingMatchesRelationshipType::assertMatch(
            $this->doctrine,
            $classMetadata->reflection,
            $config['name'],
            $config['type']
        );

        $classMetadata->addRelationship(new Relationship(
            $config['name'],
            $config['type'],
            $config['expression'] ?? null,
            (bool) ($config['include_by_default'] ?? false),
            $links
        ));
    }

}