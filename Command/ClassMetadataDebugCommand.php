<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Command;

use Metadata\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use TM\JsonApiBundle\Serializer\Configuration\Link;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\ClassMetadataInterface;
use TM\JsonApiBundle\Serializer\Configuration\Metadata\JsonApiResourceMetadataFactoryInterface;
use TM\JsonApiBundle\Serializer\Configuration\Relationship;

class ClassMetadataDebugCommand extends ContainerAwareCommand
{
    /**
     * @var JsonApiResourceMetadataFactoryInterface
     */
    private $jsonApiMetadataFactory;

    /**
     * @param JsonApiResourceMetadataFactoryInterface $jsonApiResourceMetadataFactory
     */
    public function __construct(JsonApiResourceMetadataFactoryInterface $jsonApiResourceMetadataFactory)
    {
        $this->jsonApiMetadataFactory = $jsonApiResourceMetadataFactory;

        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('debug:json-api')
            ->setDescription('Debug registered metadata for all/specified class(es)')
            ->setDefinition([
                new InputArgument('fqcn', InputArgument::REQUIRED, 'The FQCN of the class to display the data for'),
            ])
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $className = $input->getArgument('fqcn');

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf(
                'Class "%s" can not be found',
                $className
            ));
        }

        if (null === $classMetadata = $this->jsonApiMetadataFactory->getMetadataForClass($className)) {
            $output->writeln(sprintf('<error>No JSON API class metadata found for %s</error>', $className));

            exit(1);
        }

        $output->writeln(sprintf('<comment>JSON API class metadata for %s</comment>', $className));
        $output->writeln('');

        $output->writeln(Yaml::dump($this->serializeClassMetadata($classMetadata), Yaml::DUMP_OBJECT_AS_MAP));
    }

    /**
     * Serialize an instance of {@link ClassMetadataInterface} to an array for debug output
     *
     * @param ClassMetadataInterface|ClassMetadata $classMetadata
     * @return array
     */
    private function serializeClassMetadata(ClassMetadataInterface $classMetadata) : array
    {
        $serialized = [
            'type'      => $classMetadata->getDocument()->getType(),
            'id_field'  => $classMetadata->getIdField(),
        ];

        if (!$classMetadata->getLinks()->isEmpty()) {
            $serialized['links'] = [];

            foreach ($classMetadata->getLinks() as $link) {
                $serialized['links'][] = $this->serializeLink($link);
            }
        }

        if (!$classMetadata->getRelationships()->isEmpty()) {
            $serialized['relationships'] = [];

            foreach ($classMetadata->getRelationships() as $relationship) {
                $serialized['relationships'][] = $this->serializeRelationship($relationship);
            }
        }

        return [$classMetadata->name => $serialized];
    }

    /**
     * Serialize a {@link Link} object to an array
     *
     * @param Link $link
     * @return array
     */
    private function serializeLink(Link $link) : array
    {
        return [
            'name'                  => $link->getName(),
            'route_name'            => $link->getRouteName(),
            'route_parameters'      => $link->getRouteParameters(),
            'absolute'              => (bool) $link->isAbsolute(),
        ];
    }

    /**
     * Serialize a {@link Relationship} object to an array
     *
     * @param Relationship $relationship
     * @return array
     */
    private function serializeRelationship(Relationship $relationship) : array
    {
        $serialized = [
            'name'                  => $relationship->getName(),
            'mapping'               => $relationship->getMapping(),
            'expression'            => $relationship->getExpression() ?? 'None',
            'include_by_default'    => (bool) $relationship->includeByDefault(),
        ];

        if (!$relationship->getLinks()->isEmpty()) {
            $serialized['links'] = [];

            foreach ($relationship->getLinks() as $link) {
                $serialized['links'][] = $this->serializeLink($link);
            }
        }

        return $serialized;
    }
}
