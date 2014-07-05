<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 4:44 PM
 */

namespace VGR\ElasticsearchBundle\Service;

/**
 * Interface ElasticsearchEntityServiceInterface
 * @package VGR\ElasticsearchBundle\Service
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
interface ElasticsearchEntityServiceInterface
{
    /**
     * @return string JSON mapping
     */
    public function getStructure();

    /**
     * @return string
     */
    public function getIndex();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param array $lists
     */
    public function setLists($lists);

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return boolean
     */
    public function hasSubobjects();

    /**
     * @return null|array ElasticsearchEntityInterface elements
     */
    public function getSubobjects();

    /**
     * @return array
     */
    public function getSubobjectsRelatedFields();

    /**
     * @return string JSON document as elasticsearch type
     */
    public function getObjectAsDocument();
}