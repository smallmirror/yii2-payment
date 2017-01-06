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

class Purse extends BaseGateway
{
    /**
     * 支付
     * @param \app\modules\payment\models\Payment $payment
     * @return mixed
     */
    public function payment($payment)
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
    public function callback(Request $request, &$paymentId, &$money, &$message, &$payId){
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
    public function notice(Request $request, &$paymentId, &$money, &$message, &$payId){

    }
}