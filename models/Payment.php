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
 * @property integer $order_id 订单ID
 * @property integer $user_id 用户ID
 * @property string $pay_type
 * @property string $gateway 支付网关
 * @property string $password_hash
 * @property string $pay_id
 * @property integer $pay_state
 * @property integer $currency
 * @property integer $money
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $memo
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
            ['id', 'unique', 'message' => Yii::t('app', 'This id has already been taken')],
            ['pay_type', 'default', 'value' => static::TYPE_ONLINE],
            ['pay_type', 'in', 'range' => [static::TYPE_ONLINE, static::TYPE_OFFLINE, static::TYPE_RECHARGE, static::TYPE_COIN]],
            ['pay_state', 'default', 'value' => static::STATUS_NOT_PAY],
            ['pay_state', 'in', 'range' => [static::STATUS_SUCCESS, static::STATUS_FAILED, static::STATUS_REFUND, static::STATUS_NOT_PAY, static::STATUS_CLOSED, static::STATUS_REVOKED, static::STATUS_ERROR]],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'money' => Yii::t('payment', 'Money'),
            'currency' => Yii::t('payment', 'Currency'),
            'pay_type' => Yii::t('payment', 'Pay Type'),
            'gateway' => Yii::t('payment', 'Payment Gateway'),
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
            $payment->updateAttributes(['pay_id' => $params['pay_id'], 'pay_state' => static::STATUS_SUCCESS, 'memo' => $params['message']]);
            $payment->save();
            if ($payment->pay_type == static::TYPE_RECHARGE) {//充值
                Yii::$app->getModule('user')->purse($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->payment . 'Recharge');
            } else if ($payment->pay_type == static::TYPE_COIN) {//购买金币
                Yii::$app->getModule('user')->purse($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->payment . 'Recharge');

                Yii::$app->getModule('user')->purse($payment->user_id, $payment->currency, $payment->money, 'recharge', 'Buy Coin');
            }
            return true;
        }
        return false;
    }
}