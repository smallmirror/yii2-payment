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
use common\models\Purse;
use yuncms\payment\models\Payment;
use yuncms\payment\gateways\WeChat;

/**
 * Class DefaultController
 * @property \yuncms\payment\Module $module
 * @package yuncms\payment
 */
class DefaultController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                //对以下方式使用权限验证
                'only' => ['index', 'callback', 'notice', 'go'],
                'rules' => [
                    //已认证用户
                    [
                        'allow' => true,
                        'actions' => ['index', 'go', 'pay', 'query', 'return', 'query'],
                        'roles' => ['@']
                    ],
                ]
            ],
        ];
    }

    /**
     * 购买积分
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

    public function actionPay($id)
    {
        $payment = $this->findModel($id);
        if (!$this->module->hasGateway($payment->payment)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$payment->payment}'");
        }
        /** @var \yuncms\payment\BaseGateway $gateway */
        $gateway = $this->module->getGateway($payment->payment);
        $response = $gateway->payment($payment);
        return $this->render('pay', ['res' => $response, 'payment' => $payment]);
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
        if ($payment->pay_state == Payment::PAY_SUCCESS) {
            Yii::$app->getSession()->setFlash('success', Yii::t('app', 'Completion of payment.'));
        }
        return $this->redirect('/user/point/index');
    }

    /**
     * 查询支付号是否支付成功
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionQuery($id)
    {
        $payment = $this->findModel($id);
        if (!$this->module->hasGateway($payment->payment)) {
            throw new NotFoundHttpException("Unknown payment gateway '{$payment->payment}'");
        }
        /** @var \yuncms\payment\BaseGateway $gateway */
        $gateway = $this->module->getGateway($payment->payment);
        $status = $gateway->queryOrder($payment->id);
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($status) {
            return ['status' => 'succ'];
        } else {
            return ['status' => 'failed'];
        }
    }

    /**
     * @param $id
     * @return null|Payment
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