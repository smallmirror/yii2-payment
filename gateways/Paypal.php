<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace app\modules\payment\gateways;

use Yii;
use yii\helpers\Url;
use yii\web\Request;
use yii\httpclient\Client as HttpClient;
use app\modules\payment\BaseGateway;

class Paypal extends BaseGateway
{
    public $business;

    public $redirectMethod = 'POST';

    public $redirectUrl = 'https://www.paypal.com/cgi-bin/webscr';

    public function payment($payment = [])
    {
        $params = [
            'cmd' => '_xclick',
            'no_shipping' => 1,
            'business' => $this->business,
            'currency_code' => 'USD',//$payment->currency,
            'item_name' => !empty($payment->order_id) ? $payment->order_id : '充值',
            'item_number' => $payment->id,
            'amount' => round($payment->money, 2),
            'charset' => $this->charset,
            'rm' => 2,
            'return' => $this->getReturnUrl(),
            'cancel_return' => $this->getReturnUrl(),
            'notify_url' => $this->getNoticeUrl(),
        ];

        $this->getRedirectResponse($params);
    }

    public function callback(Request $request, &$paymentId, &$money, &$message, &$payId)
    {
        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }
        // post back to PayPal system to validate
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        $fp = fsockopen('www.paypal.com', 80, $errno, $errstr, 30);

        // assign posted variables to local variables
        $paymentId = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $money = !empty($_POST['payment_gross']) ? $_POST['payment_gross'] : $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $payId = $_POST['txn_id'];
        $business = $_POST['business'];

        if (!$fp) {
            fclose($fp);
            return false;
        } else {
            fputs($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets($fp, 1024);
                if (strcmp($res, 'VERIFIED') == 0) {
                    // check the payment_status is Completed
                    if ($payment_status != 'Completed' && $payment_status != 'Pending') {
                        fclose($fp);
                        return false;
                    }
                    // check that receiver_email is your Primary PayPal email
                    if ($receiver_email != $this->business) {
                        fclose($fp);
                        return false;
                    }
                    if ('USD' != $payment_currency) {
                        fclose($fp);
                        return false;
                    }
                    // 处理付款
                    fclose($fp);
                    return true;
                } elseif (strcmp($res, 'INVALID') == 0) {
                    // log for manual investigation
                    fclose($fp);
                    return false;
                }
            }
        }
    }

    public function notice(Request $request, &$paymentId, &$money, &$message, &$payId)
    {
        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }
        // post back to PayPal system to validate
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
        $fp = fsockopen('www.paypal.com', 80, $errno, $errstr, 30);

        // assign posted variables to local variables
        $paymentId = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $money = !empty($_POST['payment_gross']) ? $_POST['payment_gross'] : $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $payId = $_POST['txn_id'];
        $business = $_POST['business'];

        if (!$fp) {
            fclose($fp);
            return false;
        } else {
            fputs($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets($fp, 1024);
                if (strcmp($res, 'VERIFIED') == 0) {
                    // check the payment_status is Completed
                    if ($payment_status != 'Completed' && $payment_status != 'Pending') {
                        fclose($fp);
                        return false;
                    }
                    // check that receiver_email is your Primary PayPal email
                    if ($business != $this->business) {
                        fclose($fp);
                        return false;
                    }
                    if ('USD' != $payment_currency) {
                        fclose($fp);
                        return false;
                    }
                    // 处理付款
                    fclose($fp);
                    return true;
                } elseif (strcmp($res, 'INVALID') == 0) {
                    // log for manual investigation
                    fclose($fp);
                    return false;
                }
            }
        }
    }
}