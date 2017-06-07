<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\gateways;

use Yii;
use yii\web\Request;
use yii\base\Exception;
use yii\httpclient\Client;
use yii\base\InvalidConfigException;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;
use Endroid\QrCode\QrCode;

class Wechat extends BaseGateway
{

    /**
     * @var string 绑定支付的APPID
     */
    public $appId;

    /**
     * @var string 商户支付密钥
     */
    public $appKey;

    /**
     * @var string 商户号
     */
    public $mchId;

    /**
     * @var string 商户证书路径
     */
    public $sslCert;

    /**
     * @var string 商户账户密钥路径
     */
    public $sslKey;

    public $currencies = ['CNY', 'USD'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty ($this->appId)) {
            throw new InvalidConfigException ('The "appId" property must be set.');
        }
        if (empty ($this->appKey)) {
            throw new InvalidConfigException ('The "appKey" property must be set.');
        }
        if (empty ($this->mchId)) {
            throw new InvalidConfigException ('The "mchId" property must be set.');
        }
    }

    /**
     * 获取Http Client
     * @return Client
     */
    public function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = new Client([
                'requestConfig' => [
                    'format' => Client::FORMAT_XML
                ],
                'responseConfig' => [
                    'format' => Client::FORMAT_XML
                ],
            ]);
        }
        return $this->_httpClient;
    }

    /**
     * 编译支付参数
     * @param array $params
     * @return mixed
     */
    public function buildPaymentParameter($params = [])
    {
        $defaultParams = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => bin2hex(openssl_random_pseudo_bytes(8)),
            'notify_url' => $this->getNoticeUrl(),
            'trade_type' => 'NATIVE',
            'device_info' => 'WEB',
        ];
        return array_merge($defaultParams, $params);
    }

    /**
     * 生成支付二维码
     * @param Payment $payment
     * @param array $paymentParams 支付参数
     * @return void
     */
    public function payment(Payment $payment, &$paymentParams)
    {
        if (!$this->checkCurrency($payment->currency)) {
            Yii::$app->session->setFlash(Yii::t('payment', 'The gateway does not support the current currency!'));
        } else {
            $params = $this->buildPaymentParameter([
                'body' => !empty($payment->model_id) ? $payment->model_id : '充值',
                'out_trade_no' => $payment->id,
                'total_fee' => round($payment->money * 100),
                'fee_type' => $payment->currency,
                'spbill_create_ip' => $payment->ip,
            ]);
            $params['sign'] = $this->createSign($params);
            $response = $this->api('https://api.mch.weixin.qq.com/pay/unifiedorder', 'POST', $params);
            print_r($response->data);
            if (isset($response->data['prepay_id']) && isset($response->data['code_url'])) {
                $payment->updateAttributes(['pay_id' => $response->data['prepay_id']]);
                $paymentParams['url'] = $response->data['code_url'];
                $paymentParams['data'] = (new QrCode())->setText($response->data['code_url'])->setSize(240)->setPadding(10)->getDataUri();
            } else {
                Yii::$app->session->setFlash(Yii::t('payment', 'The server is busy. Please try again!'));
            }
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
        return;
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
        $xml = $request->getRawBody();

        //如果返回成功则验证签名
        try {
            $params = $this->convertXmlToArray($xml);
            $paymentId = $params['out_trade_no'];
            $money = $params['total_fee'];
            $message = $params['return_code'];
            $payId = $params['transaction_id'];
            if ($params['return_code'] == 'SUCCESS') {
                if ($params['sign'] == $this->createSign($params)) {
                    echo '<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>';
                    return true;
                }
            }
        } catch (\Exception $e) {

        }
        echo '<xml>
  <return_code><![CDATA[FAIL]]></return_code>
  <return_msg><![CDATA[FAIL]]></return_msg>
</xml>';
        return false;
    }

    /**
     * 查询订单支付状态
     * @param string $paymentId
     * @return boolean
     */
    public function queryOrder($paymentId)
    {
        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'out_trade_no' => $paymentId,
            'nonce_str' => bin2hex(openssl_random_pseudo_bytes(8)),
        ];
        $params['sign'] = $this->createSign($params);
        $response = $this->api('https://api.mch.weixin.qq.com/pay/orderquery', 'POST', $params);
        if ($response->data['trade_state'] == 'SUCCESS') {
            Payment::setPayStatus($paymentId, true, ['pay_id' => $response->data['transaction_id'], 'message' => $response->data['return_msg']]);
            return true;
        }
        return false;
    }

    /**
     * 关闭订单
     * @param string $paymentId
     * @return bool
     */
    public function closeOrder($paymentId){
        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'out_trade_no' => $paymentId,
            'nonce_str' => bin2hex(openssl_random_pseudo_bytes(8)),
        ];
        $params['sign'] = $this->createSign($params);
        $response = $this->api('https://api.mch.weixin.qq.com/pay/closeorder', 'POST', $params);
        if ($response->data['trade_state'] == 'SUCCESS') {
            return true;
        }
        return false;
    }


    /**
     * 生成签名
     * @param $parameters
     * @return mixed
     */
    public function createSign($parameters)
    {
        $bizParameters = [];
        foreach ($parameters as $k => $v) {
            $bizParameters[strtolower($k)] = $v;
        }
        $bizString = $this->httpBuildQueryWithoutNull($bizParameters);
        $bizString .= '&key=' . $this->appKey;
        return strtoupper(md5(urldecode($bizString)));
    }

    protected static function httpBuildQueryWithoutNull($params)
    {
        foreach ($params as $key => $value) {
            if (null == $value || 'null' == $value || 'sign' == $key) {
                unset($params[$key]);
            }
        }
        reset($params);
        ksort($params);

        return http_build_query($params);
    }

    /**
     * 转换XML到数组
     * @param \SimpleXMLElement|string $xml
     * @return array
     */
    protected function convertXmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}