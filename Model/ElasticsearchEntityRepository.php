<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 4:41 PM
 */

namespace VGR\ElasticsearchBundle\Model;

use VGR\ElasticsearchBundle\Manager\ElasticsearchManager;

/**
 * Class ElasticsearchEntityRepository
 * @package VGR\ElasticsearchBundle\Model
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
class ElasticsearchEntityRepository
{
    /**
     * @var ElasticsearchManager
     */
    protected $_em;

    /**
     * @var string
     */
    protected $_class;

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->_class = $class;
    }

    /**
     * @return string Class of entity (ElasticsearchEntityInterface)
     */
    public function getClass()
    {
        return $this->_class;
    }

    /**
     * @param ElasticsearchManager $em
     */
    public function setEm(ElasticsearchManager $em)
    {
        $this->_em = $em;
    }

    /**
     * @return ElasticsearchManager
     */
    public function getEm()
    {
        return $this->_em;
    }

    /**
     * @param ElasticSearchManager $em
     * @param null|string $class
     */
    public function __construct(ElasticSearchManager $em, $class = null)
    {
        $this->setEm($em);
        $this->setClass($class);
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $query JSON query
     * @return null|ElasticsearchEntityInterface
     */
    public function getOneByObject($method, $path, $query = null)
    {
        $object = json_decode($this->getEm()->sendRequest($method, $path, $query));

        if(empty($object->exists)&&empty($object->found))
        {
            return null;
        }else{
            $class = $this->getClass();
            $mainObject = $class::getObjectFromDocument($object);
            $this->setFieldsWithRealtedMap($mainObject);
            return $mainObject;
        }
    }

    /**
     * @return array ElasticsearchEntityInterface elements
     */
    public function getAllByObject()
    {
        $class = $this->getClass();
        $search = json_decode($this->getEm()->get('{"size": 50000000}', $class::getIndex(), $class::getType()));

        $results = array();
        if(!empty($search->hits->hits))
        {
            foreach($search->hits->hits as $object)
            {
                $mainObject = $class::getObjectFromDocument($object);
                $this->setFieldsWithRealtedMap($mainObject);
                $results[] = $mainObject;
            }
        }
        return $results;
    }

    /**
     * @param string $id
     * @return null|ElasticsearchEntityInterface
     */
    public function findOneById($id)
    {
        $class = $this->getClass();

        $search = json_decode($this->getEm()->getById($class::getIndex(), $class::getType(), $id));

        $results = null;
        if(!empty($search->_source))
        {
            $mainObject = $class::getObjectFromDocument($search);
            $this->setFieldsWithRealtedMap($mainObject);
            $results = $mainObject;
        }

        return $results;
    }

    /**
     * @param array $criteria
     * @return null|ElasticsearchEntityInterface
     */
    public function findOneBy(array $criteria)
    {
        $class = $this->getClass();

        $numCriteria = count($criteria);

        if($numCriteria == 1)
        {
            foreach($criteria as $key => $element)
            {
                $filter = '"term": {
                      "'.$class::getType().'.'.$key.'": "'.$element.'"
                    }';
            }

        }else{
            $filter = '"and": [';
            foreach($criteria as $key => $element)
            {
                $filter .= '{"term": {
                      "'.$class::getType().'.'.$key.'": "'.$element.'"
                    }},';
            }
            $filter = rtrim($filter, ',');
            $filter .= ']';
        }

        $search = json_decode($this->getEm()->get('{
              "query": {
                "constant_score": {
                  "filter": {
                    '.$filter.'
                  }
                }
              }
        }', $class::getIndex()));

        $results = null;
        if(!empty($search->hits->hits))
        {
            foreach($search->hits->hits as $object)
            {
                $mainObject = $class::getObjectFromDocument($object);
                $this->setFieldsWithRealtedMap($mainObject);
                $results = $mainObject;
            }
        }

        return $results;
    }

    /**
     * @param array $criteria
     * @return array ElasticsearchEntityInterface elements
     */
    public function findBy(array $criteria)
    {
        $class = $this->getClass();

        $numCriteria = count($criteria);

        if($numCriteria == 1)
        {
            foreach($criteria as $key => $element)
            {
                $filter = '"term": {
                      "'.$class::getType().'.'.$key.'": "'.$element.'"
                    }';
            }

        }else{
            $filter = '"and": [';
            foreach($criteria as $key => $element)
            {
                $filter .= '{"term": {
                      "'.$class::getType().'.'.$key.'": "'.$element.'"
                    }},';
            }
            $filter = rtrim($filter, ',');
            $filter .= ']';
        }

        $search = json_decode($this->getEm()->get('{
              "query": {
                "constant_score": {
                  "filter": {
                    '.$filter.'
                  }
                }
              },
              "size": 200
        }', $class::getIndex()));

        $results = array();
        foreach($search->hits->hits as $object)
        {
            $mainObject = $class::getObjectFromDocument($object);
            $this->setFieldsWithRealtedMap($mainObject);
            $results[] = $mainObject;
        }

        return $results;
    }

    /**
     * @param ElasticSearchEntityInterface $mainObject
     */
    protected function setFieldsWithRealtedMap(ElasticSearchEntityInterface $mainObject)
    {
        if($mainObject->hasSubobjects() === true)
        {
            $relatedFields = $mainObject->getSubobjectsRelatedFields();
            foreach($relatedFields as $objectField => $properties)
            {
                $methodSetObjectField = 'set'.ucfirst($objectField);
                $subobjects = array();
                $source = $this->getEm()->get('{
                        "query": {
                            "match": {
                                "'.$properties->relatedField.'": {
                                    "query": "'.urldecode($mainObject->getId()).'",
                                    "type": "phrase"
                                }
                            }
                        },
                        "size": 200
                    }', $mainObject->getIndex());
                $source = json_decode($source);
                $source = (empty($source->hits->hits))? array() : $source->hits->hits;
                foreach($source as $subobject)
                {
                    $subobjects[] = call_user_func(array($properties->relatedClass, 'getObjectFromDocument'), $subobject);
                }
                $mainObject->$methodSetObjectField($subobjects);
            }

        }
    }
}