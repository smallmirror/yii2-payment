<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\gateways;

use Yii;
use yii\web\Request;
use yii\base\InvalidConfigException;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;

/**
 * 汇潮支付 接口
 * @package yuncms\payment\gateways
 */
class Ecpss extends BaseGateway
{
    /**
     * @var string 商户号
     */
    public $merNo;

    /**
     * @var string 机密密钥
     */
    public $md5Key;

    /**
     * @var string 默认走银联
     */
    public $defaultBankNumber = 'UNIONPAY';

    /**
     * @var array 支持的币种
     */
    public $currencies = ['CNY'];

    /**
     * @var string 网关跳转方式
     */
    public $redirectMethod = 'POST';

    /**
     * @var string 网关地址
     */
    public $redirectUrl = 'https://pay.ecpss.com/sslpayment';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty ($this->merNo)) {
            throw new InvalidConfigException ('The "merNo" property must be set.');
        }
        if (empty ($this->md5Key)) {
            throw new InvalidConfigException ('The "md5Key" property must be set.');
        }
        $this->redirectUrl = $this->composeUrl($this->redirectUrl, ['_input_charset' => $this->charset]);
    }

    public function getTitle()
    {
        return Yii::t('payment','Ecpass Pay');
    }

    /**
     * 查询支付是否成功，对账作用
     * @param string $paymentId
     * @return mixed
     */
    public function queryOrder($paymentId)
    {
        return false;
    }

    /**
     * 去支付
     * @param Payment $payment
     * @param array $paymentParams 支付参数
     * @return void
     */
    public function payment(Payment $payment, &$paymentParams)
    {
        if (!$this->checkCurrency($payment->currency)) {
            Yii::$app->session->setFlash(Yii::t('payment', 'The gateway does not support the current currency!'));
        } else {
            $params = [
                'MerNo' => $this->merNo,
                'BillNo' => $payment->id,
                'Amount' => round($payment->money, 2),
                'ReturnURL' => $this->getReturnUrl(),
                'AdviceURL' => $this->getNoticeUrl(),
                'products' => !empty($payment->model_id) ? $payment->model_id : '充值',
                'Remark' => 'Remark',
                'defaultBankNumber' => $this->defaultBankNumber,
                'orderTime' => date('YmdHis')
            ];
            $md5src = $this->merNo . '&' . $payment->id . '&' . $params['Amount'] . '&' . $params['ReturnURL'] . '&' . $this->md5Key;        //校验源字符串
            $params['SignInfo'] = strtoupper(md5($md5src));//MD5检验结果
            $this->getRedirectResponse($params);
        }
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