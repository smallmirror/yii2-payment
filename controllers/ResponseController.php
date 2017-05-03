<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yuncms\payment\models\Payment;

/**
 * Class ResponseController
 *
 * @property \yuncms\payment\Module $module
 * @package app\modules\payment
 */
class ResponseController extends Controller
{

    /**
     * @var boolean whether to enable CSRF validation for the actions in this controller.
     * CSRF validation is enabled only when both this property and [[Request::enableCsrfValidation]] are true.
     */
    public $enableCsrfValidation = false;

    /**
     * @var string|null 支付号
     */
    public $paymentId = null;

    /**
     * @var string|null 平台支付号
     */
    public $payId = null;

    /**
     * @var double|null 钱数
     */
    public $money = null;

    /**
     * @var null 支付状态
     */
    public $status = null;

    /**
     * @var string|null 消息
     */
    public $message = null;

    /**
     * 支付后跳转
     * @param string $gateway
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionReturn($gateway)
    {
        if (!$this->module->hasGateway($gateway)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$gateway}'");
        }
        $gateway = $this->module->getGateway($gateway);
        $status = $gateway->callback(Yii::$app->request, $this->paymentId, $this->money, $this->message, $this->payId);
        Payment::setPayStatus($this->paymentId, $status, ['money' => $this->money, 'message' => $this->message, 'pay_id' => $this->payId]);
        return $this->redirect(['/payment/default/return', 'id' => $this->paymentId]);
    }

    /**
     * 服务器端通知
     * @param string $gateway
     * @throws NotFoundHttpException
     */
    public function actionNotice($gateway)
    {
        if (!$this->module->hasGateway($gateway)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$gateway}'");
        }
        $gateway = $this->module->getGateway($gateway);
        $status = $gateway->notice(Yii::$app->request, $this->paymentId, $this->money, $this->message, $this->payId);
        Payment::setPayStatus($this->paymentId, $status, ['money' => $this->money, 'memo' => $this->message, 'pay_id' => $this->payId]);
        Yii::$app->end();
    }
}