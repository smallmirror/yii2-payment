<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\payment;

use Yii;
use yii\web\Request;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * 网关基类
 * @package yuncms\payment
 */
abstract class BaseGateway extends Component implements GatewayInterface
{
    /**
     * @var string gateway service id.
     * This value mainly used as HTTP request parameter.
     */
    private $_id;

    /**
     * @var string auth service name.
     * This value may be used in database records, CSS files and so on.
     */
    private $_name;
    /**
     * @var string auth service title to display in views.
     */
    private $_title;

    /**
     * @var string protocol version.
     */
    public $version = '1.0';

    public $charset = 'utf-8';

    /**
     * @param string $id service id.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string service id
     */
    public function getId()
    {
        if (empty($this->_id)) {
            $this->_id = $this->getName();
        }

        return $this->_id;
    }

    /**
     * @param string $name service name.
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string service name.
     */
    public function getName()
    {
        if ($this->_name === null) {
            $this->_name = $this->defaultName();
        }

        return $this->_name;
    }

    /**
     * @param string $title service title.
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string service title.
     */
    public function getTitle()
    {
        if ($this->_title === null) {
            $this->_title = $this->defaultTitle();
        }

        return $this->_title;
    }

    /**
     * Generates service name.
     * @return string service name.
     */
    protected function defaultName()
    {
        return Inflector::camel2id(StringHelper::basename(get_class($this)));
    }

    /**
     * Generates service title.
     * @return string service title.
     */
    protected function defaultTitle()
    {
        return StringHelper::basename(get_class($this));
    }



    const CONTENT_TYPE_JSON = 'json'; // JSON format
    const CONTENT_TYPE_URLENCODED = 'urlencoded'; // urlencoded query string, like name1=value1&name2=value2
    const CONTENT_TYPE_XML = 'xml'; // XML format
    const CONTENT_TYPE_TEXT = 'text'; // attempts to determine format automatically
    const CONTENT_TYPE_AUTO = 'auto'; // attempts to determine format automatically

    /**
     * @var string API base URL.
     */
    public $apiBaseUrl;

    /**
     * @var bool test Mode
     */
    public $testMode = false;

    /**
     * @var string 币种
     */
    public $currency;

    /**
     * @var string URL, which user will be redirected after authentication at the Payment provider web site.
     * Note: this should be absolute URL (with http:// or https:// leading).
     * By default current URL will be used.
     */
    public $_returnUrl;

    /**
     * @var string 后端通知地址
     */
    public $_noticeUrl;

    /**
     * @var array cURL request options. Option values from this field will overwrite corresponding
     * values from [[defaultCurlOptions()]].
     */
    private $_curlOptions = [];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty ($this->id)) {
            throw new InvalidConfigException ('The "id" property must be set.');
        }
    }

    /**
     * @param array $curlOptions cURL options.
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->_curlOptions = $curlOptions;
    }

    /**
     * @return array cURL options.
     */
    public function getCurlOptions()
    {
        return $this->_curlOptions;
    }

    /**
     * 获取是否是测试模式
     * @return bool 是否是测试模式
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * 设置是否是测试模式
     * @param boolean $value 是否是测试模式
     * @return mixed
     */
    public function setTestMode($value)
    {
        return $this->testMode = $value;
    }

    /**
     * 获取支付币种
     * @return mixed
     */
    public function getCurrency()
    {
        return strtoupper($this->currency);
    }

    /**
     * Get the payment currency number.
     *
     * @return integer
     */
    public function getCurrencyNumeric()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getNumeric();
        }
    }

    /**
     * Get the number of decimal places in the payment currency.
     *
     * @return integer
     */
    public function getCurrencyDecimalPlaces()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getDecimals();
        }

        return 2;
    }

    /**
     * 设置支付币种
     * @param string $value
     * @return mixed
     */
    public function setCurrency($value)
    {
        if ($value instanceof Currency) {
            return $this->currency = $value->getCode();
        } else {
            return $this->currency = strtoupper($value);
        }
    }

    /**
     * @param string $returnUrl return URL
     */
    public function setReturnUrl($returnUrl)
    {
        $this->_returnUrl = $returnUrl;
    }

    /**
     * @return string return URL.
     */
    public function getReturnUrl()
    {
        if ($this->_returnUrl === null) {
            $this->_returnUrl = $this->defaultReturnUrl();
        }
        return $this->_returnUrl;
    }

    /**
     * @param string $noticeUrl return URL
     */
    public function setNoticeUrl($noticeUrl)
    {
        $this->_noticeUrl = $noticeUrl;
    }

    /**
     * @return string return URL.
     */
    public function getNoticeUrl()
    {
        if ($this->_noticeUrl === null) {
            $this->_noticeUrl = $this->defaultNoticeUrl();
        }
        return $this->_noticeUrl;
    }

    /**
     * Sets persistent state.
     * @param string $key state key.
     * @param mixed $value state value
     * @return $this the object itself
     */
    protected function setState($key, $value)
    {
        if (!Yii::$app->has('session')) {
            return $this;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $session->set($key, $value);
        return $this;
    }

    /**
     * Returns persistent state value.
     * @param string $key state key.
     * @return mixed state value.
     */
    protected function getState($key)
    {
        if (!Yii::$app->has('session')) {
            return null;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $value = $session->get($key);
        return $value;
    }

    /**
     * Removes persistent state value.
     * @param string $key state key.
     * @return boolean success.
     */
    protected function removeState($key)
    {
        if (!Yii::$app->has('session')) {
            return true;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $session->remove($key);
        return true;
    }

    /**
     * Returns session key prefix, which is used to store internal states.
     * @return string session key prefix.
     */
    protected function getStateKeyPrefix()
    {
        return get_class($this) . '_';
    }

    /**
     * Composes default [[returnUrl]] value.
     * @return string return URL.
     */
    public function defaultReturnUrl()
    {
        return Url::to(['/payment/response/return', 'gateway' => $this->id], true);
    }

    /**
     * Composes default [[noticeUrl]] value.
     * @return string return URL.
     */
    public function defaultNoticeUrl()
    {
        return Url::to(['/payment/response/notice', 'gateway' => $this->id], true);
    }

    public $redirectMethod = 'POST';
    public $redirectUrl;

    public function getRedirectResponse($params)
    {
        if ('GET' === $this->redirectMethod) {
            $url = $this->composeUrl($this->redirectUrl, $params);
            Yii::$app->response->redirect($url);
            Yii::$app->end();

        } elseif ('POST' === $this->redirectMethod) {
            $hiddenFields = '';
            foreach ($params as $key => $value) {
                $hiddenFields .= sprintf(
                        '<input type="hidden" name="%1$s" value="%2$s" />',
                        htmlentities($key, ENT_QUOTES, 'UTF-8', false),
                        htmlentities($value, ENT_QUOTES, 'UTF-8', false)
                    )."\n";
            }

            $output = '<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Redirecting...</title>
    </head>
    <body onload="document.forms[0].submit();" style=" text-align:center;background-color: #eff0f1;">
		<img src="data:image/gif;base64,R0lGODlhMgAyAKIHAP////vZu/JyBfvavvvavfJ2DPN4D////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFBwAHACwAAAAAMgAyAEADpXi63P4wygmFETTLwHvIQCgCWjOKpXOGqcmmaNuupGzfeK5PwfAxgqAQw4jtjjlj8mWjuWoLJ3JKrVqvk6EQe/B0GhZidHXs/UrKJnMJ1UjVJ65c8ZbUcel7Gruf+/+AgYKDO0MFhApaFnIeBA0FQodFIztePSBkOgQdjm6UiKBccWxrFHcwmZ59qKuhrqRtE6OwpRCnMrN0n2OtR7d/ua/Cw8Q7CQAh+QQFBwAHACwMAAgAGwALAEADNHi6rPEtHkArkFHoLVRtTwgxG8aIoTlZFDguXLleaqbVjoib6IuxrhSJM7PsYjfg7hA7JAAAIfkEBQcABwAsFgAIABUAFQBAAzd4B6z+MMZAa5DYic0F+81TZWRpHpwCfqfDihbVKuMcpXOXrvManjxY7RSTtWK2pLKky+kEzlQCACH5BAUHAAcALCAADAALABsAQAM0eAes/jC6QJW4WDzGm6RgdWBRx5WeNIlPCKpORkIzZNomgJ9w7x8uiIulCP58MppM80g+EgAh+QQFBwAHACwWABYAFQAVAEADOHi63AfQyamEvaKFTfuDINBtJNdcXiqFoLq0KjmhTCkvGO2q7M5Grt5uSNSUdjYTDmO8LXM7qCsBACH5BAUHAAcALAwAIAAbAAsAQAMzeCes/tCBGWO4OFTHensYNI2T120iSS2fk4XPiELwzJ62RN5efqgUEy2Tks1eF9/mZUsAACH5BAUHAAcALAgAFgAVABUAQAM4eCes/jDKOYOFIGvAeqOVJQZgiWWK15nPyobke4ziCcjHpi2erH4sFW5ILNFktMvr+NDJdKiXMwEAIfkEBQcABwAsCAAMAAsAGwBAAzR4utL7cIUZH7gYvMbb/FSlYFHHlY4UPuAnvmN2RSR6bqYAmXCvuqxWANLyGSsyiGxmyRwSADs=" style="width:80px;margin-left:auto; margin-top:200px ">
        <form action="%1$s" method="post" style="opacity:0">
            <p>Redirecting to payment page...</p>
            <p>
                %2$s
                <input type="submit" value="Continue" />
            </p>
        </form>
    </body>
</html>';
            $output = sprintf(
                $output,
                htmlentities($this->redirectUrl, ENT_QUOTES, 'UTF-8', false),
                $hiddenFields
            );

            Yii::$app->response->content = $output;
            Yii::$app->end();
        }

        throw new \Exception('Invalid redirect method "'.$this->redirectMethod.'".');
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
    abstract public function callback(Request $request, &$paymentId, &$money, &$message, &$payId);

    /**
     * 服务端通知
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    abstract public function notice(Request $request, &$paymentId, &$money, &$message, &$payId);

    /**
     * 合并 CUrl 参数.
     * If each options array has an element with the same key value, the latter
     * will overwrite the former.
     * @param array $options1 options to be merged to.
     * @param array $options2 options to be merged from. You can specify additional
     * arrays via third argument, fourth argument etc.
     * @return array merged options (the original options are not changed.)
     */
    protected function mergeCurlOptions($options1, $options2)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_array($v) && !empty($res[$k]) && is_array($res[$k])) {
                    $res[$k] = array_merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }

    /**
     * Returns default cURL options.
     * @return array cURL options.
     */
    protected function defaultCurlOptions()
    {
        return [
            CURLOPT_USERAGENT => Yii::$app->name . ' Gateway ' . $this->version . ' Client',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
    }

    /**
     * Sends HTTP request.
     * @param string $method request type.
     * @param string $url request URL.
     * @param array $params request params.
     * @param array $headers additional request headers.
     * @return array response.
     * @throws Exception on failure.
     */
    protected function sendRequest($method, $url, array $params = [], array $headers = [])
    {
        $curlOptions = $this->mergeCurlOptions(
            $this->defaultCurlOptions(),
            $this->getCurlOptions(),
            [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => $url,
            ],
            $this->composeRequestCurlOptions(strtoupper($method), $url, $params)
        );
        $curlResource = curl_init();
        foreach ($curlOptions as $option => $value) {
            curl_setopt($curlResource, $option, $value);
        }
        $response = curl_exec($curlResource);
        $responseHeaders = curl_getinfo($curlResource);

        // check cURL error
        $errorNumber = curl_errno($curlResource);
        $errorMessage = curl_error($curlResource);

        curl_close($curlResource);

        if ($errorNumber > 0) {
            throw new Exception('Curl error requesting "' . $url . '": #' . $errorNumber . ' - ' . $errorMessage);
        }
        if (strncmp($responseHeaders['http_code'], '20', 2) !== 0) {
            throw new InvalidResponseException($responseHeaders, $response, 'Request failed with code: ' . $responseHeaders['http_code'] . ', message: ' . $response);
        }
        return $this->processResponse($response, $this->determineContentTypeByHeaders($responseHeaders));
    }

    /**
     * Processes raw response converting it to actual data.
     * @param string $rawResponse raw response.
     * @param string $contentType response content type.
     * @throws Exception on failure.
     * @return array actual response.
     */
    protected function processResponse($rawResponse, $contentType = self::CONTENT_TYPE_AUTO)
    {
        if (empty($rawResponse)) {
            return [];
        }
        switch ($contentType) {
            case self::CONTENT_TYPE_AUTO: {
                $contentType = $this->determineContentTypeByRaw($rawResponse);
                if ($contentType == self::CONTENT_TYPE_AUTO) {
                    throw new Exception('Unable to determine response content type automatically.');
                }
                $response = $this->processResponse($rawResponse, $contentType);
                break;
            }
            case self::CONTENT_TYPE_TEXT: {
                $response = $rawResponse;
                break;
            }
            case self::CONTENT_TYPE_JSON: {
                $response = Json::decode($rawResponse, true);
                break;
            }
            case self::CONTENT_TYPE_URLENCODED: {
                $response = [];
                parse_str($rawResponse, $response);
                break;
            }
            case self::CONTENT_TYPE_XML: {
                $response = $this->convertXmlToArray($rawResponse);
                break;
            }
            default: {
                throw new Exception('Unknown response type "' . $contentType . '".');
            }
        }
        return $response;
    }

    /**
     * Converts XML document to array.
     * @param string|\SimpleXMLElement $xml xml to process.
     * @return array XML array representation.
     */
    protected function convertXmlToArray($xml)
    {
        if (!is_object($xml)) {
            $xml = simplexml_load_string($xml);
        }
        $result = (array)$xml;
        foreach ($result as $key => $value) {
            if (is_object($value)) {
                $result[$key] = $this->convertXmlToArray($value);
            }
        }
        return $result;
    }

    /**
     * Composes URL from base URL and GET params.
     * @param string $url base URL.
     * @param array $params GET params.
     * @return string composed URL.
     */
    protected function composeUrl($url, array $params = [])
    {
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return $url;
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
                $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
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

    /**
     * Attempts to determine the content type from raw content.
     * @param string $rawContent raw response content.
     * @return string response type.
     */
    protected function determineContentTypeByRaw($rawContent)
    {
        if (preg_match('/^\\{.*\\}$/is', $rawContent)) {
            return self::CONTENT_TYPE_JSON;
        }
        if (preg_match('/^[^=|^&]+=[^=|^&]+(&[^=|^&]+=[^=|^&]+)*$/is', $rawContent)) {
            return self::CONTENT_TYPE_URLENCODED;
        }
        if (preg_match('/^<.*>$/is', $rawContent)) {
            return self::CONTENT_TYPE_XML;
        }
        return self::CONTENT_TYPE_AUTO;
    }

    /**
     * Attempts to determine HTTP request content type by headers.
     * @param array $headers request headers.
     * @return string content type.
     */
    protected function determineContentTypeByHeaders(array $headers)
    {
        if (isset($headers['content_type'])) {
            if (stripos($headers['content_type'], 'json') !== false) {
                return self::CONTENT_TYPE_JSON;
            }
            if (stripos($headers['content_type'], 'urlencoded') !== false) {
                return self::CONTENT_TYPE_URLENCODED;
            }
            if (stripos($headers['content_type'], 'xml') !== false) {
                return self::CONTENT_TYPE_XML;
            }
        }
        return self::CONTENT_TYPE_AUTO;
    }

    /**
     * Performs request to the OAuth API.
     * @param string $apiUrl API sub URL.
     * @param string $method request method.
     * @param array $params request parameters.
     * @param array $headers additional request headers.
     * @return array API response
     * @throws Exception on failure.
     */
    public function api($apiUrl, $method = 'GET', array $params = [], array $headers = [])
    {
        if (preg_match('/^https?:\\/\\//is', $apiUrl)) {
            $url = $apiUrl;
        } else {
            $url = $this->apiBaseUrl . '/' . $apiUrl;
        }
        return $this->apiInternal($url, $method, $params, $headers);
    }

    /**
     * Performs request to the OAuth API.
     * @param string $url absolute API URL.
     * @param string $method request method.
     * @param array $params request parameters.
     * @param array $headers additional request headers.
     * @return array API response.
     * @throws Exception on failure.
     */
    protected function apiInternal($url, $method, array $params, array $headers)
    {
        return $this->sendRequest($method, $url, $params, $headers);
    }

    /**
     * 去支付
     * @param \app\modules\payment\models\Payment $payment
     * @return mixed
     */
    abstract function payment($payment);

}