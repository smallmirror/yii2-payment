<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\gateways;

use Yii;
use yii\web\Request;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;

/**
 * Class Purse
 * @package yuncms\payment\gateways
 */
class Purse extends BaseGateway
{
    /**
     * 支付
     * @param Payment $payment
     * @param array $paymentParams 支付参数
     * @return mixed
     */
    public function payment(Payment $payment, &$paymentParams)
    {
        //从余额扣钱并返回
    }

    /**
     * 支付响应
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    public function callback(Request $request, &$paymentId, &$money, &$message, &$payId)
    {
        echo '开始跳转';
    }

    /**
     * 服务端通知
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    public function notice(Request $request, &$paymentId, &$money, &$message, &$payId)
    {

    }

    /**
     * 查询支付是否成功，对账作用
     * @param string $paymentId
     * @return mixed
     */
    public function queryOrder($paymentId)
    {

    }
}