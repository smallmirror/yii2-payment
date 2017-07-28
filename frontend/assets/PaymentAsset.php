<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\frontend\assets;


use yii\web\AssetBundle;
/**
 * Class PaymentAsset
 * @package yuncms\payment\assets
 */
class PaymentAsset extends AssetBundle
{
    public $sourcePath = '@yuncms/payment/frontend/views/assets';

    /**
     * @var array
     */
    public $js = [
        'js/pay.js'
    ];

    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}