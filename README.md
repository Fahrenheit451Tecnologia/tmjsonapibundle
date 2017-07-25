TMJsonApiBundle
===============

This bundle provides a way to easily render your API responses with [JSON API](http://jsonapi.org/) specification format.

Instalation
-----------

1. Include in your project `composer.json` the following dependency:

```yaml
"require": {
        ...
        "tm/tm-jsonapi-bundle": "0.1.1"
    },
```

2. Add a repository key to your project `composer.json`:

```yaml
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
<?php
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

```yaml
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

- Create a REST route and controller following FosRestBundle instructions:

```yaml
#app/config/routing.yml
...
app_api:
  type: rest
  prefix: api
  resource: "@AppBundle/Resources/config/routing.yml"
```

```yaml
#src/AppBundle/Resources/config/routing.yml
api_cats:
    type: rest
    resource: "@AppBundle/Controller/CatController.php"
```
Controller

```php
<?php
...
    public function getCatsAction()
    {

        return $this->em
            ->getRepository(Cat::class)
            ->findAll();
    }
```
- Create a model and register it as JsonApi

```php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TM\JsonApiBundle\Serializer\Configuration\Annotation as JsonApi;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Cat
 * @package AppBundle\Resources\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CatRepository")
 * @ORM\Table(name="cats", schema="feline")
 * @JsonApi\Document(type="cats")
 * @Serializer\ExclusionPolicy("ALL")
 *
 */
class Cat
{
    /**
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @JsonApi\Id()
     * @Serializer\Expose()
     * @var
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="text", nullable=false, length=40)
     * @Serializer\Expose()
     * @var
     */
    protected $name;
    
    // more properties and accessors
}
```

Credits
-------

This package has been heavily influenced by steffenbrem/json-api-bundle, willdurand/hateoas and others. Full attributions and licences will be added soon.