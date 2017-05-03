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
use yii\base\InvalidConfigException;
use yuncms\payment\BaseGateway;
use yuncms\payment\models\Payment;
use yuncms\wallet\models\WalletLog;
use yuncms\wallet\models\Wallet as WalletModel;

/**
 * Class Purse
 * @package yuncms\payment\gateways
 */
class Wallet extends BaseGateway
{
    public $md5Key;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->redirectUrl = $this->getReturnUrl();
        if (empty ($this->md5Key)) {
            throw new InvalidConfigException ('The "md5Key" property must be set.');
        }
    }

    /**
     * 支付
     * @param Payment $payment
     * @param array $paymentParams 支付参数
     * @return mixed
     */
    public function payment(Payment $payment, &$paymentParams)
    {
        $wallet = WalletModel::findByUserID($payment->user_id, $payment->currency);
        $value = $wallet->money - $payment->money;

        $params = [
            'paymentId' => $payment->id,
            'money' => $payment->money,
        ];

        if ($payment->money < 0 || $value < 0) {
            Yii::$app->session->setFlash(Yii::t('payment', 'The server is busy, please try again later!'));
            $params['payId'] = '0';
            $params['status'] = 'failure';
            $params['message'] = 'Insufficient balance.';
        } else {
            $transaction = WalletModel::getDb()->beginTransaction();
            try {
                //更新用户钱包
                $wallet->updateAttributes(['money' => $value, 'updated_at' => time()]);
                //创建钱包操作日志
                $log = new WalletLog([
                    'wallet_id' => $wallet->id,
                    'currency' => $payment->currency,
                    'money' => $payment->money,
                    'action' => 'Purchase',
                    'msg' => '',
                    'type' => $payment->money > 0 ? WalletLog::TYPE_INC : WalletLog::TYPE_DEC
                ]);
                $log->link('wallet', $wallet);
                $transaction->commit();
                $params['payId'] = $log->id;
                $params['status'] = 'completion';
                $params['message'] = 'Completion of payment.';
            } catch (\Exception $e) {
                $transaction->rollBack();
                $params['payId'] = '0';
                $params['status'] = 'failure';
                $params['message'] = 'Insufficient balance.';
            }
        }
        $md5src = $params['paymentId'] . '&' . $params['money'] . '&' . $this->md5Key;        //校验源字符串
        $params['sign'] = strtoupper(md5($md5src));//MD5检验结果
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
        $return = $request->post();
        // 订单号
        $payId = trim($return ['payId']);
        $paymentId = trim($return ['paymentId']);
        $money = trim($return ['money']);
        $status = trim($return ['status']);
        // 备注
        $message = trim($return ['message']);
        $SignMD5info = trim($return ['sign']);
        $sign = strtoupper(md5("{$paymentId}&{$money}&{$this->md5Key}"));
        if ($sign == $SignMD5info) {
            if ($status == "completion" && WalletLog::find()->where(['id' => $payId])->exists()) {
                return true;
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
        return true;
    }

    /**
     * 查询支付是否成功，对账作用
     * @param string $paymentId
     * @return mixed
     */
    public function queryOrder($paymentId)
    {
        return true;
    }
}