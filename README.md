TMJsonApiBundle
===============

This bundle provides a way to easily render your API responses with [JSON API](http://jsonapi.org/) specification format.

Instalation
-----------

1. Include in your project `composer.json` the following dependency:

```yml
"require": {
        ...
        "tm/tm-jsonapi-bundle": "0.1.1"
    },
```

2. Add a repository key to your project `composer.json`:

```yml
"repositories": [
        {
            "type": "git",
            "url": "git@github.com:Fahrenheit451Tecnologia/tmjsonapibundle.git"
        }
    ],
```

3. Run `composer update` or `composer install` and you should be getting it

4. Register the bundle and its requirements in the `AppKernel.php` file:

```php
public function registerBundles()
    {
        $bundles = [
            ...
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\AopBundle\JMSAopBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new TM\JsonApiBundle\TMJsonApiBundle(),
        ];

        ...

        return $bundles;
    }
```

Configuration
-------------

Add the following basic configuration in your project `app/config/config.yml`:

```yml
fos_rest:
    body_listener: true
    param_fetcher_listener: true
    format_listener:
        rules:
            - { path: '^/api', priorities: ['json'], fallback_format: json, prefer_extension: false }
            - { path: '^/', stop: true }
    view:
        view_response_listener: 'force'
        failed_validation: 422
        mime_types:
            json: [ 'application/vnd.api+json', 'application/json' ]

jms_di_extra:
    disable_grep: true
    locations:
        directories: [ '%kernel.root_dir%/../src', '%kernel.root_dir%/../vendor/tm' ]

jms_serializer:
    property_naming:
        separator:  _
        lower_case: true
```

For more detailed configuration refer to each bundle documentation

Usage
-----

Credits
-------

This package has been heavily influenced by steffenbrem/json-api-bundle, willdurand/hateoas and others. Full attributions and licences will be added soon.