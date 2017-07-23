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

4. Register the bundle in the `AppKernel.php` file:

```php
public function registerBundles()
    {
        $bundles = [
            ...
            new TM\JsonApiBundle\TMJsonApiBundle(),
        ];

        ...

        return $bundles;
    }
```

Credits
-------

This package has been heavily influenced by steffenbrem/json-api-bundle, willdurand/hateoas and others. Full attributions and licences will be added soon.