<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 4:41 PM
 */

namespace VGR\ElasticsearchBundle\Model;

use VGR\ElasticsearchBundle\Service\ElasticsearchEntityService;

/**
 * Class ElasticsearchEntity
 * @package VGR\ElasticsearchBundle\Model
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
abstract class ElasticsearchEntity implements ElasticsearchEntityInterface
{
    /**
     * @var null|ElasticsearchEntityService
     */
    public static $_elasticsearchEntityService;

    /**
     * @param string $method
     * @param array $args
     * @return bool|ElasticsearchEntityService
     */
    public function __call($method, $args) {
        try{
            $reflectionMethod = static::getElasticsearchEntityService($this)->getMethod($method);
        }catch(\ReflectionException $e){
            new \Exception('Method not found');
            return false;
        }

        return call_user_func_array(array($reflectionMethod, 'invoke'), array_merge(array(static::getElasticsearchEntityService($this)), $args));
    }

    /**
     * @param string $method
     * @param array $args
     * @return bool|ElasticsearchEntityService
     */
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
     * @param ElasticsearchEntityInterface $mainObject
     * @return ElasticsearchEntityService
     */
    public static function getElasticsearchEntityService($mainObject)
    {
        static::$_elasticsearchEntityService = new \VGR\ElasticsearchBundle\Service\ElasticsearchEntityService($mainObject);

        return static::$_elasticsearchEntityService;
    }
}