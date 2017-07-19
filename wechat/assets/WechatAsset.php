<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\wechat\assets;

use yii\web\AssetBundle;

/**
 * WechatAsset
 *
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class WechatAsset extends AssetBundle
{
    public $sourcePath = '@yuncms/payment/wechat/views/assets';

    /**
     * @var array
     */
    public $css = [
        //'css/wechat.css'
    ];

    /**
     * @var array
     */
    public $js = [
        'js/wechat.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        //'yuncms\live\assets\LiveAsset'
    ];
}