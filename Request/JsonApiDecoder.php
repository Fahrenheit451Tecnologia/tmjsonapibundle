<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Request;

use FOS\RestBundle\Decoder\DecoderInterface;
use TM\JsonApiBundle\Exception\ExceptionFactory;

class JsonApiDecoder implements DecoderInterface
{
    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param JsonApiRequest $jsonApiRequest
     * @param DecoderInterface $jsonDecoder
     */
    public function __construct(JsonApiRequest $jsonApiRequest, DecoderInterface $jsonDecoder)
    {
        $this->jsonApiRequest = $jsonApiRequest;
        $this->jsonDecoder = $jsonDecoder;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        $json = $this->jsonDecoder->decode($data);

        if (!$this->jsonApiRequest->hasJsonApiContentType()) {
            return $json;
        }

        $this->processJsonArray($json);

        if (!empty($this->errors)) {
            throw ExceptionFactory::requestBodyNotValidJsonApiSchema($data, $this->errors);
        }

        return $this->jsonApiRequest->getParameters()->all();
    }

    /**
     * @param array|null $json
     * @return JsonApiDecoder
     */
    private function processJsonArray(array $json = null) : JsonApiDecoder
    {
        if (null === $json) {
            return $this->addError('Request body does not contain valid json');
        }

        if (!array_key_exists('data', $json)) {
            return $this->addError('Request body does not contain a data key');
        }

        if (!array_key_exists('type', $json['data'])) {
            return $this->addError('Resource "type" is required', '/data/type');
        }

        $this->jsonApiRequest->setType($json['data']['type']);
        $this->jsonApiRequest->addJsonPointer('type', '/data/type');

        if (array_key_exists('id', $json['data'])) {
            $this->jsonApiRequest->setId($json['data']['id']);
            $this->jsonApiRequest->addJsonPointer('id', '/data/id');
        }

        foreach (['attributes', 'relationships'] as $group) {
            foreach (['type', 'id'] as $field) {
                if (array_key_exists($group, $json['data']) && array_key_exists($field, $json['data'][$group])) {
                    $this->addError(
                        sprintf('"%s" can not have a field named "%s"', $group, $field),
                        sprintf('/data/%s/%s', $group, $field)
                    );
                }
            }
        }

        if (array_key_exists('attributes', $json['data'])) {
            if (!is_array($json['data']['attributes'])) {
                $this->addError('/data/attributes must be an object', '/data/attributes');
            } else {
                foreach ($json['data']['attributes'] as $attribute => $value) {
                    $this->jsonApiRequest->addParameter($attribute, $value);
                    $this->jsonApiRequest->addJsonPointer($attribute, sprintf('/data/attributes/%s', $attribute));
                }
            }
        }

        if (array_key_exists('relationships', $json['data'])) {
            if (!is_array($json['data']['relationships'])) {
                $this->addError('/data/relationships must be an object', '/data/relationships');
            } else {
                foreach ($json['data']['relationships'] as $relationship => $value) {
                    if (!array_key_exists('data', $value)) {
                        if (!array_key_exists('links', $value) && !array_key_exists('meta', $value)) {
                            $this->addError(
                                'Relationship must have at least one of: data, meta or links',
                                sprintf('/data/relationships/%s', $relationship)
                            );
                        }
                        
                        continue;
                    }

                    if (isset($value['data']['type'])) {
                        if (!array_key_exists('id', $value['data'])) {
                            $this->addError(
                                'Relationship must contain an id',
                                sprintf('/data/relationships/%s/data/id', $relationship)
                            );

                            continue;
                        }

                        $this->jsonApiRequest->addParameter($relationship, $value['data']['id']);
                        $this->jsonApiRequest->addJsonPointer(
                            $relationship,
                            sprintf('/data/relationships/%s', $relationship)
                        );
                    } elseif (is_array($value['data'])) {
                        $ids = [];

                        foreach ($value['data'] as $i => $data) {
                            foreach (['type', 'id'] as $key) {
                                if (!array_key_exists($key, $data)) {
                                    $this->addError(
                                        sprintf('Relationship %s is required', $key),
                                        sprintf('/data/relationships/%s/data/%s/%s', $relationship, $i, $key)
                                    );

                                    break 2;
                                }
                            }

                            $ids[] = $data['id'];
                        }

                        $this->jsonApiRequest->addParameter($relationship, !empty($ids) ? $ids : null);
                        $this->jsonApiRequest->addJsonPointer(
                            $relationship,
                            sprintf('/data/relationships/%s', $relationship)
                        );
                    }
                }
            }
        }

        foreach ($json['data'] as $key => $value) {
            if (!in_array($key, ['id', 'type', 'attributes', 'relationships', 'links', 'meta'])) {
                $this->addError(
                    sprintf('/data/%s is not a valid top-level member', $key),
                    sprintf('/data/%s', $key)
                );
            }
        }

        return $this;
    }

    /**
     * Add json schema error
     *
     * @param string $message
     * @param string|null $pointer
     * @return JsonApiDecoder
     */
    private function addError(string $message, string $pointer = null) : JsonApiDecoder
    {
        $error = [
            'message'   => $message,
        ];

        if (null !== $pointer) {
            $error['pointer'] = $pointer;
        }

        $this->errors[] = $error;

        return $this;
    }
}