<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonPointerOptionExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('json_api_pointer', 'attributes')
            ->setAllowedTypes('json_api_pointer', ['string'])
            ->setAllowedValues('json_api_pointer', ['attributes', 'relationships'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * @return iterable
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}