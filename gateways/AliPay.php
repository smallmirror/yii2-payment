<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\payment\gateways;

use Yii;
use yii\web\Request;
use yii\httpclient\Client as HttpClient;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;

/**
 * Class AliPay
 * @package yuncms\payment\gateways
 */
class AliPay extends BaseGateway
{
    public $partner;
    public $seller_email;
    public $key;

    public $signType = 'MD5';

    public $redirectMethod = 'POST';

    public $redirectUrl = 'https://mapi.alipay.com/gateway.do';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->signType = strtoupper($this->signType);
        $this->redirectUrl = $this->composeUrl($this->redirectUrl, ['_input_charset' => $this->charset]);
    }

    public function buildPaymentParameter($params = [])
    {
        $defaultParams = [
            'service' => "create_direct_pay_by_user",
            'partner' => $this->partner,
            'seller_email' => $this->seller_email,
            'payment_type' => 1,
            'notify_url' => $this->getNoticeUrl(),
            'return_url' => $this->getReturnUrl(),
            'anti_phishing_key' => '',
            'exter_invoke_ip' => Yii::$app->request->userIP,
            '_input_charset' => strtolower($this->charset),
        ];
        return array_merge($defaultParams, $params);
    }

    /**
     * 支付
     * @param Payment $payment
     * @return void
     */
    public function payment(Payment $payment)
    {
        $params = $this->buildPaymentParameter([
            'out_trade_no' => $payment->id,
            'subject' => !empty($payment->order_id) ? $payment->order_id . '付款' : '账号充值',
            'total_fee' => round($payment->money, 2),
        ]);
        //签名结果与签名方式加入请求提交参数组中
        $paraFilter = $this->paraFilter($params);
        $paraSort = $this->argSort($paraFilter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkString($paraSort);

        $params['sign'] = $this->createSign($prestr);
        $params['sign_type'] = strtoupper($this->signType);
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
        $return = $request->get();
        unset($return['gateway']);
        $paymentId = $return['out_trade_no'];
        $payId = $return['trade_no'];
        $money = $return['total_fee'];
        $message = $return['trade_status'];
        return $this->getResponse($return);
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
        $return = $request->post();
        $paymentId = $return['out_trade_no'];
        $payId = $return['trade_no'];
        $money = $return['total_fee'];
        $message = $return['trade_status'];
        return $this->getResponse($return);
    }

    /**
     * 联机验证支付状态
     */
    public function getResponse($return)
    {
        $paraFilter = $this->paraFilter($return);
        $paraSort = $this->argSort($paraFilter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkString($paraSort);

        $sign = $this->createSign($prestr);

        $isSign = $sign == $return["sign"];

        $responseTxt = 'true';
        if (!empty($return["notify_id"])) {
            $responseTxt = $this->api('https://mapi.alipay.com/gateway.do', 'GET', ['service' => 'notify_verify', 'notify_id' => trim($return["notify_id"]), 'partner' => $this->partner]);
        }
        //验证
        //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关

        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送Http请求
     * @param string $method request type.
     * @param string $apiUrl request URL.
     * @param array $params request params.
     * @param array $headers additional request headers.
     * @return array response.
     * @throws \yii\base\Exception on failure.
     */
    public function api($apiUrl, $method = 'GET', array $params = [], array $headers = [])
    {
        $client = new HttpClient();
        $response = $client->createRequest()
            ->setMethod($method)
            ->addHeaders($headers)
            ->setUrl($apiUrl)
            ->setData($params)
            ->send();
        return $response->content;
    }

    /**
     * 生成签名
     * @param string $bizString
     * @return string
     */
    private function createSign($bizString)
    {
        return md5($bizString . $this->key);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    private function createLinkString($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        return $arg;
    }

    /**
     * 对数组排序
     * @param array $para 排序前的数组
     * @return array  排序后的数组
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param array $para 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para)
    {
        $paraFilter = [];
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") {
                continue;
            } else {
                $paraFilter[$key] = $para[$key];
            }
        }
        return $paraFilter;
    }
}