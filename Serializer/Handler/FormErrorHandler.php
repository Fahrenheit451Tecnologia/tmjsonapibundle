<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Handler;

use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TM\JsonApiBundle\Model\Error;
use TM\JsonApiBundle\Model\Source;
use TM\JsonApiBundle\Request\JsonApiRequest;

class FormErrorHandler implements SubscribingHandlerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * @param TranslatorInterface $translator
     * @param JsonApiRequest $jsonApiRequest
     */
    public function __construct(TranslatorInterface $translator, JsonApiRequest $jsonApiRequest)
    {
        $this->translator = $translator;
        $this->jsonApiRequest = $jsonApiRequest;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods() : array
    {
        return [
            [
                'type'      => Form::class,
                'method'    => 'serializeFormToJson',
            ], [
                'type'      => FormError::class,
                'method'    => 'serializeFormErrorToJson',
            ]
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Form $form
     * @param array $type
     * @return mixed
     */
    public function serializeFormToJson(JsonSerializationVisitor $visitor, Form $form, array $type)
    {
        $errors = [];

        $this->convertFormToArray($errors, $visitor, $form);

        return ['errors' => array_map(function(Error $error) {
            return $error->toJson();
        }, $errors)];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param FormError $formError
     * @param array $type
     * @return array
     */
    public function serializeFormErrorToJson(JsonSerializationVisitor $visitor, FormError $formError, array $type)
    {
        $error = $this->convertFormErrorToError($formError);

        if (null === $visitor->getRoot()) {
            $visitor->setRoot(['errors' => [$error]]);
        }

        return $error;
    }

    /**
     * @param array $errors
     * @param JsonSerializationVisitor $visitor
     * @param Form $form
     * @return Form
     */
    private function convertFormToArray(array &$errors, JsonSerializationVisitor $visitor, Form $form)
    {
        $isRoot = null === $visitor->getRoot();

        foreach ($form->getErrors() as $error) {
            // Do not add "This form should not contain extra fields" error as they are handled below
            if (array_key_exists('{{ extra_fields }}', $error->getMessageParameters())) {
                continue;
            }

            $errors[] = $this->convertFormErrorToError($error);
        }

        foreach ($form->all() as $child) {
            if ($child instanceof Form) {
                $this->convertFormToArray($errors, $visitor, $child);
            }
        }

        foreach ($form->getExtraData() as $name => $value) {
            $errors[] = Error::create()
                ->setStatus('422')
                ->setCode('INVALID_PROPERTY')
                ->setDetail('This property should not be included in this request')
                ->setSource(Source::fromPointer($this->jsonApiRequest->getJsonPointerMap()->get($name, 'unknown')))
            ;
        }

        if ($isRoot) {
            $visitor->setRoot(['errors' => $errors]);
        }

        return $form;
    }

    /**
     * @param FormError $error
     * @return Error
     */
    private function convertFormErrorToError(FormError $error)
    {
        /** @var FormInterface $form */
        $form = $error->getOrigin();
        $pointer = 'attributes';
        $name = [];

        do {
            array_unshift($name, $form->getName());

            if ('' !== $form->getName()) {
                $pointer = $form->getConfig()->getOption('json_api_pointer');
            }
        } while ($form = $form->getParent());

        $name = implode('/', array_filter($name));

        return Error::create()
            ->setStatus('422')
            ->setCode('INVALID_VALUE')
            ->setDetail($this->getErrorMessage($error))
            ->setSource(Source::fromPointer(sprintf(
                '/data/%s%s%s',
                $pointer,
                '' !== $name ? '/' : '',
                '' !== $name ? $name : ''
            )))
        ;
    }

    /**
     * @param FormError $error
     * @return string
     */
    private function getErrorMessage(FormError $error) : string
    {
        if (null !== $error->getMessagePluralization()) {
            return $this->translator->transChoice(
                $error->getMessageTemplate(),
                $error->getMessagePluralization(),
                $error->getMessageParameters(),
                'validators'
            );
        }

        return $this->translator->trans(
            $error->getMessageTemplate(),
            $error->getMessageParameters(),
            'validators'
        );
    }
}