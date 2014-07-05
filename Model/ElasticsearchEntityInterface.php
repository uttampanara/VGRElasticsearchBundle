<?php
/**
 * Created by PhpStorm.
 * User: vgrdominik
 * Date: 3/26/14
 * Time: 4:44 PM
 */

namespace VGR\ElasticsearchBundle\Model;

/**
 * Interface ElasticsearchEntityInterface
 * @package VGR\ElasticsearchBundle\Model
 * @author Valentí Gàmez Rojas <vgr.gamez@gmail.com>
 */
interface ElasticsearchEntityInterface
{
    /**
     * @return array
     */
    public function getLists();

    /**
     * @return string JSON mapping
     */
    public static function getStructure();

    /**
     * @return string
     */
    public static function getIndex();

    /**
     * @return string
     */
    public static function getType();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return boolean
     */
    public function hasSubobjects();

    /**
     * @return array
     */
    public function getSubobjects();

    /**
     * @return boolean|array
     */
    public function getSubobjectsRelatedFields();
}