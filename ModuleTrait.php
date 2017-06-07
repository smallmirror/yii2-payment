<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment;

use Yii;

/**
 * Trait ModuleTrait
 * @property-read Module $module
 * @package yuncms\payment
 */
trait ModuleTrait
{
    /**
     * 获取模块实例
     * @return Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('payment');
    }
}