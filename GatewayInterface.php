<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\payment;

/**
 * GatewayInterface declares basic interface all Gateway clients should follow.
 */
interface GatewayInterface
{
    /**
     * @param string $id service id.
     */
    public function setId($id);

    /**
     * @return string service id
     */
    public function getId();

    /**
     * @return string service name.
     */
    public function getName();

    /**
     * @param string $name service name.
     */
    public function setName($name);

    /**
     * @return string service title.
     */
    public function getTitle();

    /**
     * @param string $title service title.
     */
    public function setTitle($title);
}