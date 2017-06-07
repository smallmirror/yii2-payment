<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\models;

use Yii;
use yii\db\Query;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Payment ActiveRecord model
 *
 * Database fields:
 * @property integer $id 付款ID
 * @property integer $model_id 订单ID
 * @property string $model 订单模型
 * @property integer $user_id 用户ID
 * @property string $pay_type
 * @property string $gateway 支付网关
 * @property string $pay_id
 * @property integer $pay_state
 * @property integer $currency
 * @property integer $money
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $note 备注
 * @property string $return_url 支付后的跳转URL
 * @property string $ip
 * @package yuncms\payment
 */
class Payment extends ActiveRecord
{
    //在线支付
    const TYPE_ONLINE = 1;

    //离线
    const TYPE_OFFLINE = 2;

    //充值
    const TYPE_RECHARGE = 3;

    //购买金币
    const TYPE_COIN = 4;

    //原生扫码支付
    const TRADE_TYPE_NATIVE = 1;

    //公众号支付
    const TRADE_TYPE_JSAPI = 2;

    //app支付
    const TRADE_TYPE_APP = 3;

    //支付状态
    //未支付
    const STATUS_NOT_PAY = 0;
    //支付成功
    const STATUS_SUCCESS = 1;
    //支付失败
    const STATUS_FAILED = 2;
    //转入退款
    const STATUS_REFUND = 3;
    //已关闭
    const STATUS_CLOSED = 4;
    //已撤销
    const STATUS_REVOKED = 5;
    //错误
    const STATUS_ERROR = 6;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%payment}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            [['gateway', 'currency', 'pay_type', 'money'], 'required'],
            ['id', 'unique', 'message' => Yii::t('payment', 'This id has already been taken')],
            ['model_id', 'integer'],
            ['model', 'string', 'max' => 255],
            ['pay_type', 'default', 'value' => static::TYPE_ONLINE],
            ['pay_type', 'in', 'range' => [static::TYPE_ONLINE, static::TYPE_OFFLINE, static::TYPE_RECHARGE, static::TYPE_COIN]],

            ['trade_type', 'default', 'value' => static::TRADE_TYPE_NATIVE],
            ['trade_type', 'in', 'range' => [ static::TRADE_TYPE_NATIVE, static::TRADE_TYPE_JSAPI, static::TRADE_TYPE_APP]],
            ['pay_state', 'default', 'value' => static::STATUS_NOT_PAY],
            ['pay_state', 'in', 'range' => [static::STATUS_SUCCESS, static::STATUS_FAILED, static::STATUS_REFUND, static::STATUS_NOT_PAY, static::STATUS_CLOSED, static::STATUS_REVOKED, static::STATUS_ERROR]],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('payment', 'ID'),
            'model_id' => Yii::t('payment', 'Model ID'),
            'model' => Yii::t('payment', 'Model'),
            'pay_id' => Yii::t('payment', 'Pay ID'),
            'user_id' => Yii::t('payment', 'User ID'),
            'name' => Yii::t('payment', 'Payment Name'),
            'gateway' => Yii::t('payment', 'Payment Gateway'),
            'currency' => Yii::t('payment', 'Currency'),
            'money' => Yii::t('payment', 'Money'),
            'pay_type' => Yii::t('payment', 'Pay Type'),
            'pay_state' => Yii::t('payment', 'Pay State'),
            'ip' => Yii::t('payment', 'Pay IP'),
            'note' => Yii::t('payment', 'Pay Note'),
            'created_at' => Yii::t('payment', 'Created At'),
            'updated_at' => Yii::t('payment', 'Updated At'),
        ];
    }

    /**
     * User Relation
     * @return \yii\db\ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(Yii::$app->user->identityClass, ['id' => 'user_id']);
    }

    /**
     * 生成付款流水号
     */
    public function generatePaymentId()
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad($i, 4, '0', STR_PAD_LEFT);
            $row = (new Query())->from(static::tableName())->where(['id' => $id])->exists();
        } while ($row);
        return $id;
    }

    /**
     * 保存前
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->id = $this->generatePaymentId();
                $this->user_id = Yii::$app->user->getId();
                $this->ip = Yii::$app->request->userIP;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 快速创建实例
     * @param array $attribute
     * @return mixed
     */
    public static function create(array $attribute)
    {
        $model = new static ($attribute);
        if ($model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 计算用户获得的积分数量
     * @param integer $money 钱数
     * @param string $currency 币种
     * @return float
     */
    private static function getCoin($money, $currency = 'CNY')
    {
        $coin = $money;
        if ($currency == 'CNY') {//10比1
            if ($money >= 200 && $money < 500) {
                $coin += $coin * 0.5;
            } else if ($money >= 500) {//冲多少送多少
                $coin += $coin;
            }
        } else if ($currency == 'USD') {//2比1
            if ($money >= 20) {
                $coin += $coin * 0.1;
            } else if ($money >= 50 && $money < 100) {
                $coin += $coin * 0.2;
            } else if ($money >= 100) {//冲多少送多少
                $coin += $coin;
            }
        }
        //test
        if ($money == 0.01) {
            $coin = 0.01;
        }
        return $coin;
    }

    /**
     * 设置支付状态
     * @param string $paymentId
     * @param int $status
     * @param array $params
     * @return bool
     */
    public static function setPayStatus($paymentId, $status, $params)
    {
        if (($payment = static::findOne(['id' => $paymentId])) == null) {
            return false;
        }
        if (static::STATUS_SUCCESS == $payment->pay_state) {
            return true;
        }

        if ($status == true) {
            $payment->updateAttributes(['pay_id' => $params['pay_id'], 'pay_state' => static::STATUS_SUCCESS, 'note' => $params['message']]);
            if ($payment->pay_type == static::TYPE_ONLINE) {//在线支付订单
                /** @var \yuncms\payment\OrderInterface $orderModel */
                $orderModel = $payment->model;
                if (!empty($payment->model_id) && !empty($orderModel)) {
                    $orderModel::setPayStatus($payment->model_id, $paymentId, $status, $params);
                }
            } else if ($payment->pay_type == static::TYPE_OFFLINE) {//离线支付

            } else if ($payment->pay_type == static::TYPE_RECHARGE) {//充值
                /** @var \yuncms\wallet\Module $wallet */
                $wallet = Yii::$app->getModule('wallet');
                $wallet->wallet($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->gateway . ' Recharge');
            } else if ($payment->pay_type == static::TYPE_COIN) {//购买金币
                /** @var \yuncms\wallet\Module $wallet */
                $wallet = Yii::$app->getModule('wallet');
                $wallet->wallet($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->gateway . ' Recharge');
                $wallet->wallet($payment->user_id, $payment->currency, -$payment->money, 'purchase', 'Buy Coin');

                $coin = static::getCoin($payment->money, $payment->currency);
                /** @var \yuncms\user\Module $user */
                $user = Yii::$app->getModule('user');
                $user->coin($payment->user_id, 'purchase', $coin);
            }
            return true;
        }
        return false;
    }
}