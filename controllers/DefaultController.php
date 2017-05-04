<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\controllers;


use Yii;
use yii\web\Response;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yuncms\payment\models\Payment;

/**
 * Class DefaultController
 * @property \yuncms\payment\Module $module
 * @package yuncms\payment
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    //已认证用户
                    [
                        'allow' => true,
                        'actions' => ['index', 'pay', 'query'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['return'],
                        'roles' => ['@', '?']
                    ],
                ]
            ],
        ];
    }

    /**
     * 支付默认表单
     * @return string
     */
    public function actionIndex()
    {
        $model = new Payment();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['/payment/default/pay', 'id' => $model->id]);
        }
        return $this->render('index', ['model' => $model]);
    }

    /**
     * 去付款
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionPay($id)
    {
        $payment = $this->findModel($id);
        if (!$this->module->hasGateway($payment->gateway)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$payment->gateway}'");
        }
        /** @var \yuncms\payment\BaseGateway $gateway */
        $gateway = $this->module->getGateway($payment->gateway);
        $paymentParams = [];
        $gateway->payment($payment, $paymentParams);
        if ($paymentParams) {
            return $this->render('pay', ['payment' => $payment, 'paymentParams' => $paymentParams]);
        }
        return $this->redirect(['/payment/default/index', 'id' => $payment->id]);
    }

    /**
     * 支付后回跳页面
     * @param string $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionReturn($id)
    {
        $payment = $this->findModel($id);
        if (!empty($payment->return_url)) {
            Yii::$app->session->setFlash('success', Yii::t('payment', 'Completion of payment.'));
            return $this->redirect($payment->return_url);
        }
        return $this->render('return', ['payment' => $payment]);
    }

    /**
     * 查询支付号是否支付成功
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionQuery($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $payment = $this->findModel($id);
        if (!$this->module->hasGateway($payment->gateway)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$payment->payment}'");
        }
        /** @var \yuncms\payment\BaseGateway $gateway */
        $gateway = $this->module->getGateway($payment->gateway);
        $status = $gateway->queryOrder($payment->id);
        if ($status) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'pending'];
        }
    }

    /**
     * 获取支付单号
     * @param int $id
     * @return Payment
     * @throws NotFoundHttpException
     */
    public function findModel($id)
    {
        if (($model = Payment::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}