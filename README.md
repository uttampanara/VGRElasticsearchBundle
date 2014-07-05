VGRElasticsearchBundle
======================

About
-----

This bundle provides easy elasticsearch management support for Symfony2.

## Installation

To install this bundle, you'll need PHP >= 5.3 and Symfony >= 2.3.

### Step 1: Download VGRElasticsearchBundle using composer

Tell composer to require VGRElasticsearchBundle by running the command:

``` bash
$ php composer.phar require "vgrdominik/elasticsearch-bundle:dev-master"
```

### Step 2: Enable the bundles

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...

        new VGR\ElasticsearchBundle\VGRElasticsearchBundle()
    );
}
```

## Configuration
### Elasticsearch parameters:

``` yaml
# app/config/parameters.yml
elasticsearch_host: 'localhost'
elasticsearch_port: 9200
elasticsearch_credentials: null
```

Create entity
-------------

Extends class VGR\ElasticsearchBundle\Model\ElasticsearchEntity in an entity.
The methods used are very simple.

``` php

use VGR\ElasticsearchBundle\Model\ElasticsearchEntity;

class MyEntity extends ElasticsearchEntity
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @param array $location
     */
    public function setLocation($location)
    {
        if(!empty($location[0])&&!empty($location[1]))
        {
            $this->setLatitude($location[0]);
            $this->setLongitude($location[1]);
        }
    }

    /**
     * @param \stdClass $place
     */
    public function setPlace($place)
    {
        foreach($place as $propertyName => $property)
        {
            call_user_func(array($this, 'set'.ucfirst($propertyName)), $property);
        }
    }

    /**
     * @return \stdClass
     */
    public function getPlace()
    {
        $place = new \stdClass();
        $place->location = array($this->getLatitude(), $this->getLongitude());

        return $place;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return str_replace('.', '', $this->getLatitude().$this->getLongitude());
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = floatval($latitude);
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return (empty($this->latitude))? 0 : $this->latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = floatval($longitude);
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return (empty($this->longitude))? 0 : $this->longitude;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public static function getStructure()
    {
        return '{
                  "'.self::getType().'": {
                    "properties": {
                      "name": {
                        "type": "string"
                      },
                      "place": {
                        "properties": {
                          "location": {
                            "type": "geo_point"
                          }
                        }
                      }
                    }
                  }
                }';
    }

    public static function getType()
    {
        return 'mytype';
    }

    public static function getIndex()
    {
        return 'myindex';
    }

    public function hasSubobjects()
    {
        return false;
    }

    public function getSubobjects()
    {
        return array();
    }

    public function getSubobjectsRelatedFields()
    {
        return false;
    }

    public function getLists()
    {
        return array();
    }
}
```

If you must extends from another entity, you must implement VGR\ElasticsearchBundle\Model\ElasticsearchEntityInterface
and add magic methods __call, __callStatic and static method getElasticsearchEntityService as this.

``` php

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use VGR\ElasticsearchBundle\Model\ElasticsearchEntityInterface;
use FOS\OAuthServerBundle\Util\Random;

class Client extends BaseClient implements ElasticsearchEntityInterface
{
    public static $_elasticsearchEntityService;

    public function __call($method, $args) {
        try{
            $reflectionMethod = static::getElasticsearchEntityService($this)->getMethod($method);
        }catch(\ReflectionException $e){
            new \Exception('Method not found');
            return false;
        }

        return call_user_func_array(array($reflectionMethod, 'invoke'), array_merge(array(static::getElasticsearchEntityService($this)), $args));
    }

    public static function __callStatic($method, $args) {
        try{
            $reflectionMethod = static::getElasticsearchEntityService(get_called_class())->getMethod($method);
        }catch(\ReflectionException $e){
            new \Exception('Method not found');
            return false;
        }

        return call_user_func_array(array($reflectionMethod, 'invoke'), array_merge(array(static::getElasticsearchEntityService(get_called_class())), $args));
    }

    /**
     * @return ElasticsearchEntityService
     */
    public static function getElasticsearchEntityService($mainObject)
    {
        static::$_elasticsearchEntityService = new \VGR\ElasticsearchBundle\Service\ElasticsearchEntityService($mainObject);

        return static::$_elasticsearchEntityService;
    }

    protected $id;

    public function __construct()
    {
        parent::__construct();
        $this->setId(Random::generateToken());
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public static function getStructure()
    {
        return '{
                  "'.self::getType().'": {
                    "properties": {
                      "id": {
                        "type": "string",
                        "index": "not_analyzed"
                      },
                      "randomId": {
                        "type": "string",
                        "index": "not_analyzed"
                      },
                      "secret": {
                        "type": "string",
                        "index": "not_analyzed"
                      },
                      "lists" : {
                        "properties" : {
                            "redirectUris" : {"type" : "string"}
                        }
                      }
                    }
                  }
                }';
    }

    public function getLists()
    {
        return array(
            "redirectUris" => $this->getRedirectUris()
        );
    }

    public static function getIndex()
    {
        return 'oauth';
    }

    public static function getType()
    {
        return 'client';
    }

    public function hasSubobjects()
    {
        return false;
    }

    public function getSubobjects()
    {
        return array();
    }

    public function getSubobjectsRelatedFields()
    {
        return false;
    }
}
```

Get entity manager
------------------

``` php

$esm = $this->getContainer()->get('vgr.elasticsearch.manager.elasticsearch');
```

Generate mapping
----------------

Create command to generate indexs and types.

``` php

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MyNamespace\MyBundle\Entity\MyEntity;

class CreateModelCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('my_app:model:create')
            ->setDescription('Create model')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $esm = $this->getContainer()->get('vgr.elasticsearch.manager.elasticsearch');

        $output->writeln('Creating index "'.MyEntity::getIndex().'".');
        $esm->createIndex(MyEntity::getIndex());
        $output->writeln('Index "'.MyEntity::getIndex().'" created.');
        $output->writeln('Creating mapping "'.MyEntity::getPath().'".');
        $esm->createMapping(MyEntity::getIndex(), MyEntity::getType(),  MyEntity::getStructure());
        $output->writeln('Mapping "'.MyEntity::getPath().'" created.');
    }
}
```

``` bash

php app/console my_app:model:create
```

Create repository
-----------------

Extends class VGR\ElasticsearchBundle\Model\ElasticsearchEntityRepository in an entity.

``` php

use VGR\ElasticsearchBundle\Model\ElasticsearchEntityRepository;

class MyEntityRepository extends ElasticsearchEntityRepository
{

    public function findMyEntitiesByGeolocation($lat, $lng)
    {
        $class = $this->getClass();
        $q = json_decode($this->getEm()->get('{
              "sort": [
                {
                  "_geo_distance": {
                    "location": [
                      '.$lat.',
                      '.$lng.'
                    ],
                    "order": "asc",
                    "unit": "km"
                  }
                }
              ],
              "size": 20
            }', $class::getIndex()));

        if(empty($q->hits->hits))
        {
            return null;
        }else{
            $allEntities = array();
            foreach($q->hits->hits as $entity)
            {
                $entity = $entity->_source;
                $allEntities[] = $entity;
            }
            return $allEntities;
        }

    }
}
```

Examples
--------

Get a document as entity from repository.

``` php

$myEntityRepository = $esm->getRepository('Test\CoreBundle\Entity\MyEntity');
$myEntity = $myEntityRepository->findOneById('4150210');
```

Create a document from entity if not exists.

``` php

$myEntityRepository = $esm->getRepository('Test\CoreBundle\Entity\MyEntity');
$myEntity = $myEntityRepository->findOneById('41521');
if(empty($myEntity))
{
    $myEntity = new MyEntity();
    $myEntity->setLatitude(41.5);
    $myEntity->setLongitude(2.1);
    $myEntity->setName('test');

    $esm->persist($myEntity);

    $output->writeln('Created new document in elasticsearch');
}else{
    $output->writeln('Document found in elasticsearch');
}
```