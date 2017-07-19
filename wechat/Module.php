<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\wechat;

use Yii;
use yii\base\InvalidParamException;

/**
 * Class Module
 * Example application configuration:
 *
 * ```php
 * 'modules' => [
 *     'payment' => [
 *         'class'         => 'yuncms\payment\wechat\Module',
 *         'collection'    => [
 *             'PayPal' => [
 *                 'purse'     => $params['paypal_purse'],
 *                 'secret'    => $params['paypal_secret'],   /// NEVER keep secret in source control
 *             ],
 *             'webmoney_usd' => [
 *                 'gateway'   => 'WebMoney',
 *                 'purse'     => $params['webmoney_purse'],
 *                 'secret'    => $params['webmoney_secret'], /// NEVER keep secret in source control
 *             ],
 *         ],
 *     ],
 * ],
 * ```
 * @package Payment
 */
class Module extends \yuncms\payment\Module
{

    /**
     * 初始化
     */
    public function init()
    {
        parent::init();
        if (!$this->hasGateway('wechat')) {//检查是否注册了微信支付
            throw new InvalidParamException("Unknown gateway 'wechat'.");
        }
    }
}