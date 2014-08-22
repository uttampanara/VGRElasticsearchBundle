<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 9:19 AM
 */

namespace VGR\ElasticsearchBundle\Manager;

use VGR\ElasticsearchBundle\Model\ElasticsearchEntityInterface;
use VGR\ElasticsearchBundle\Model\ElasticsearchEntityRepository;


/**
 * Class ElasticsearchManager
 * @package VGR\ElasticsearchBundle\Manager
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
class ElasticsearchManager
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @example user:PASSWORD
     * @var null|string
     */
    private $credentials;

    /**
     * @param string $host
     * @param string $port
     * @param string $credentials
     */
    public function __construct($host, $port, $credentials = null)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setCredentials($credentials);
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param null|string $credentials
     */
    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * @return null|string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param $name
     * @return string JSON Response
     */
    public function createIndex($name)
    {
        return $this->sendRequest('PUT', '/'.$name.'/');
    }

    /**
     * @param string $index Name of index
     * @param string $type Name of type
     * @param string $map JSON of mapping
     * @return string JSON Response
     */
    public function createMapping($index, $type, $map)
    {
        $return = $this->sendRequest('PUT', '/'.$index.'/'.$type.'/_mapping?ignore_conflicts=true', $map);
        return $return;
    }

    /**
     * @param string $index
     * @param string $type
     * @return string JSON Response
     */
    public function count($index, $type)
    {
        $path = '/'.$index.'/'.$type.'/'.'_count';
        return $this->sendRequest('GET', $path);
    }

    /**
     * @param string $map JSON query
     * @param string $index
     * @param string $type
     * @return string JSON Response
     */
    public function get($map, $index = '', $type = '')
    {
        $path = '/'.$index.'/'.$type.'/'.'_search';

        return $this->sendRequest('GET', $path, $map); // '/'.$index.'/'.$type.
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $map JSON query
     * @return string JSON Response
     */
    public function delete($index, $type, $map)
    {
        if($type === null)
        {
            $path = '/'.$index.'/'.'_query';
        }else{
            $path = '/'.$index.'/'.$type.'/'.'_query';
        }
        return $this->sendRequest('DELETE', $path, $map);
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $id
     * @return string JSON Response
     */
    public function deleteById($index, $type, $id)
    {
        $path = '/'.$index.'/'.$type.'/'.$id;
        return $this->sendRequest('DELETE', $path);
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $id
     * @return string JSON Response
     */
    public function getById($index, $type, $id)
    {
        $path = '/'.$index.'/'.$type.'/'.$id;
        return $this->sendRequest('GET', $path);
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $id
     * @param string $document JSON
     * @return string JSON Response
     */
    public function put($index, $type, $id, $document)
    {
        return $this->sendRequest('POST', '/'.$index.'/'.$type.'/'.$id, $document);
    }

    /**
     * @param string $method
     * @param string $path
     * @param null|string $query
     * @return string JSON Response
     */
    public function sendRequest($method, $path, $query = null)
    {
        $url = $this->getHost().":".$this->getPort().$path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $credentials = $this->getCredentials();
	    if($credentials !== null)
        {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_USERPWD, $credentials);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($query !== null)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;

    }

    /**
     * @param ElasticsearchEntityInterface $entity
     * @return string JSON Response
     */
    public function persist(ElasticsearchEntityInterface $entity)
    {
        if($entity->hasSubobjects() === true)
        {
            $subobjects = $entity->getSubobjects();
            $relatedFields = $entity->getSubobjectsRelatedFields();
            foreach($relatedFields as $properties)
            {
                $relatedClass = $properties->relatedClass;
                $response = $this->delete($relatedClass::getIndex(), $relatedClass::getType(), '{
                          "query": {
                            "term": {
                                "'.$properties->relatedField.'": "'.$entity->getId().'"
                            }
                          },
                          "size": 200
                        }');
            }
            if(!empty($subobjects))
            {
                foreach($subobjects as $subobject)
                {
                    $response = $this->persist($subobject);
                }
            }
        }
        return $this->put($entity->getIndex(), $entity->getType(), $entity->getId(), $entity->getObjectAsDocument());
    }

    /**
     * @param string $class
     * @return ElasticsearchEntityRepository
     */
    public function getRepository($class)
    {
        // $class -> ElasticsearchEntityInterface string
        $class = new $class();
        $classRepository = $class->getRepository();
        return new $classRepository($this, $class);
    }
}
