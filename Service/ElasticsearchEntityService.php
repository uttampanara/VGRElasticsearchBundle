<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 4:41 PM
 */

namespace VGR\ElasticsearchBundle\Service;

use VGR\ElasticsearchBundle\Model\ElasticsearchEntityInterface;

/**
 * Class ElasticsearchEntityService
 * @package VGR\ElasticsearchBundle\Service
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
class ElasticsearchEntityService implements ElasticsearchEntityServiceInterface
{
    /**
     * @var string
     */
    protected $_repository;

    /**
     * @var null|string|ElasticsearchEntityInterface
     */
    protected $mainObject;

    /**
     * @param null|string|ElasticsearchEntityInterface $mainObject
     */
    public function __construct($mainObject = null)
    {
        $this->setMainObject($mainObject);
    }

    /**
     * @param string $method
     * @return \ReflectionMethod
     */
    public function getMethod($method)
    {
        $instantiatedClass = new static;
        $reflection = new \ReflectionClass($instantiatedClass);
        return $reflection->getMethod($method);
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return '';
    }

    /**
     * @return null
     */
    public function getId()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getStructure()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $class = $this->getMainObject();
        $className = $this->getMainObjectClassName();

        if(is_object($class))
        {
            return $class->getIndex().'/'.$class->getType();
        }else{
            return $className::getIndex().'/'.$className::getType();
        }

    }

    /**
     * @param array $lists
     */
    public function setLists($lists)
    {
        $class = $this->getMainObject();

        if(!empty($lists))
        {
            foreach($lists as $listKey => $list)
            {
                call_user_func(array($class, 'set'.ucfirst($listKey)), $list);
            }
        }
    }

    /**
     * @param string $repository
     */
    public function setRepository($repository)
    {
        $this->getMainObject()->_repository = $repository;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        $class = $this->getMainObject();
        if(is_object($class))
        {
            return (empty($class->_repository))? 'VGR\ElasticsearchBundle\Model\ElasticsearchEntityRepository' : $class->_repository;
        }else{
            return 'VGR\ElasticsearchBundle\Model\ElasticsearchEntityRepository';
        }
    }

    /**
     * @param null|string|ElasticsearchEntityInterface $mainObject
     */
    public function setMainObject($mainObject)
    {
        $this->mainObject = $mainObject;
    }

    /**
     * @return null|string|ElasticsearchEntityInterface
     */
    public function getMainObject()
    {
        return $this->mainObject;
    }

    /**
     * @return null|string
     */
    public function getMainObjectClassName()
    {
        $class = $this->getMainObject();

        if(is_object($class))
        {
            $className = get_class($class);
        }else{
            $className = $class;
        }

        return $className;
    }

    /**
     * @return null|array ElasticsearchEntityInterface elements
     */
    public function getSubobjects()
    {
        return $this->getMainObject()->getSubobjects();
    }

    /**
     * @return array
     */
    public function getSubobjectsRelatedFields()
    {
        return $this->getMainObject()->getSubobjectsRelatedFields();
    }

    /**
     * @return boolean
     */
    public function hasSubobjects()
    {
        return $this->getMainObject()->hasSubobjects();
    }

    /**
     * @return string JSON document as elasticsearch type
     */
    public function getObjectAsDocument()
    {
        $class = $this->getMainObject();
        $document = new \stdClass();
        $structure = json_decode($this->getMainObject()->getStructure());
        $type = $class->getType();

        foreach($structure->$type->properties as $nameOfProperty => $property)
        {
            $document->$nameOfProperty = call_user_func(array($class, 'get'.ucfirst($nameOfProperty)));
        }

        return json_encode($document);
    }

    /**
     * @param \stdClass $document As elasticsearch type
     * @return ElasticsearchEntityInterface
     */
    public function getObjectFromDocument(\stdClass $document)
    {
        $class = $this->getMainObject();
        $className = $this->getMainObjectClassName();

        $entity = new $className();
        $structure = json_decode($className::getStructure());
        $type = $className::getType();

        foreach($structure->$type->properties as $nameOfProperty => $property)
        {
            call_user_func(array($entity, 'set'.ucfirst($nameOfProperty)), (empty($document->_source->$nameOfProperty))? null : $document->_source->$nameOfProperty);
        }
        if(method_exists($entity, 'setId'))
        {
            $entity->setId($document->_id);
        }

        return $entity;
    }
}