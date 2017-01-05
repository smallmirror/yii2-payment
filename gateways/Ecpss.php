<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\gateways;

use Yii;
use yii\helpers\Url;
use yii\web\Request;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;


class Ecpss extends BaseGateway
{
    public $merNo;
    public $md5Key;

    public $redirectMethod = 'POST';
    public $redirectUrl = 'https://pay.ecpss.com/sslpayment';

    /**
     * @param array $payment
     * @return array
     */
    public function payment($payment = [])
    {
        $params = [
            'MerNo' => $this->merNo,
            'BillNo' => $payment->id,
            'Amount' => round($payment->money, 2),
            'ReturnURL' => $this->getReturnUrl(),
            'AdviceURL' => $this->getNoticeUrl(),
            'products' => !empty($payment->order_id) ? $payment->order_id : '充值',
            'Remark' => 'Remark',
            'defaultBankNumber' => 'UNIONPAY',//默认走银联
            'orderTime' => date('YmdHis')
        ];
        $md5src = $this->merNo . '&' . $payment->id . '&' . $params['Amount'] . '&' . $params['ReturnURL'] . '&' . $this->md5Key;        //校验源字符串
        $params['SignInfo'] = strtoupper(md5($md5src));//MD5检验结果
        $this->getRedirectResponse($params);
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
        $receiveData = $this->filterParameter($request->post());
        if ($receiveData) {
            // 订单号
            $payId = trim($receiveData ['BillNo']);
            $paymentId = trim($receiveData ['BillNo']);
            $money = trim($receiveData ['Amount']);
            $status = trim($receiveData ['Succeed']);
            $SignMD5info = trim($receiveData ['SignMD5info']);
            // 备注
            $message = trim($receiveData ['Remark']);
            $sign = strtoupper(md5("{$paymentId}&{$money}&{$status}&{$this->md5Key}"));
            if ($sign == $SignMD5info) {
                if ($status == "88") {
                    return true;
                }
            }
        }
        return false;
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
        $receiveData = $this->filterParameter($request->post());
        if ($receiveData) {
            // 订单号
            $payId = trim($receiveData ['BillNo']);
            $paymentId = trim($receiveData ['BillNo']);
            $money = trim($receiveData ['Amount']);
            $status = trim($receiveData ['Succeed']);
            $SignMD5info = trim($receiveData ['SignMD5info']);
            // 备注
            $message = trim($receiveData ['Remark']);

            $sign = strtoupper(md5("{$paymentId}&{$money}&{$status}&{$this->md5Key}"));
            if ($sign == $SignMD5info) {
                if ($status == "88") {
                    return true;
                }
            }
        }
        return false;

    }

    /**
     * 返回字符过滤
     * @param array $parameter
     * @return array
     */
    private function filterParameter($parameter)
    {
        $para = [];
        foreach ($parameter as $key => $value) {
            if ('' == $value || 'm' == $key || 'a' == $key || 'c' == $key || 'code' == $key)
                continue;
            else
                $para [$key] = $value;
        }
        return $para;
    }
}