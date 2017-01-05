<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\payment\gateways;

use Yii;
use yuncms\payment\BaseGateway;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\Request;
use yuncms\payment\models\Payment;

class WeChat extends BaseGateway
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
     * @var string 公众帐号secert
     */
    public $appSecret;

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
     * 支付
     * @param \app\modules\payment\models\Payment $payment
     * @return mixed
     */
    public function payment($payment)
    {
        $params = $this->buildPaymentParameter([
            'body' => !empty($payment->order_id) ? $payment->order_id : '充值',
            'out_trade_no' => $payment->id,
            'total_fee' => round($payment->money * 100),
            'fee_type' => $payment->currency,
            'spbill_create_ip' => $payment->ip,
        ]);
        $params['sign'] = $this->createSign($params);
        $response = $this->api('https://api.mch.weixin.qq.com/pay/unifiedorder', 'POST', $params);
        if (isset($response['prepay_id'])) {
            $payment->setAttribute('pay_id', $response['prepay_id']);
            $payment->save();
        }
        if (isset($response['code_url'])) {
            $qrCode = new \Endroid\QrCode\QrCode(); // Use Endroid\QrCode to generate the QR code
            $response['qrCode'] = $qrCode->setText($response['code_url'])->setSize(240)->setPadding(10)->getDataUri();
        }
        return $response;
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
        $xml = $request->getRawBody();

        //如果返回成功则验证签名
        try {
            $params = $this->convertXmlToArray($xml);

            $paymentId = $params['out_trade_no'];
            $money = $params['total_fee'];
            $message = $params['return_code'];
            $payId = $params['transaction_id'];
            if($params['return_code'] == 'SUCCESS'){
                if($params['sign'] == $this->createSign($params)){
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
     * @param $payment
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
        if ($response['trade_state'] == 'SUCCESS') {
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
     * Composes HTTP request CUrl options, which will be merged with the default ones.
     * @param string $method request type.
     * @param string $url request URL.
     * @param array $params request params.
     * @return array CUrl options.
     * @throws Exception on failure.
     */
    protected function composeRequestCurlOptions($method, $url, array $params)
    {
        $curlOptions = [];
        switch ($method) {
            case 'GET': {
                $curlOptions[CURLOPT_URL] = $this->composeUrl($url, $params);
                break;
            }
            case 'POST': {

                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_HTTPHEADER] = ['Content-type: application/x-www-form-urlencoded'];
                $curlOptions[CURLOPT_POSTFIELDS] = $this->convertArrayToXml($params);
                break;
            }
            case 'HEAD': {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                if (!empty($params)) {
                    $curlOptions[CURLOPT_URL] = $this->composeUrl($url, $params);
                }
                break;
            }
            default: {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                if (!empty($params)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = $params;
                }
            }
        }
        return $curlOptions;
    }

    protected function processResponse($rawResponse, $contentType = self::CONTENT_TYPE_AUTO)
    {
        $contentType = self::CONTENT_TYPE_XML;
        return parent::processResponse($rawResponse, $contentType);
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

    /**
     * 将数组转换成xml
     * @param array $array
     * @return string
     */
    protected function convertArrayToXml(array $array)
    {
        $xml = "<xml>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}