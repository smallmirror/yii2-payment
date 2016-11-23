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
 * @property integer $id
 * @property integer $order_id
 * @property integer $user_id
 * @property string $pay_type
 * @property string $password_hash
 * @property string $pay_id
 * @property integer $pay_state
 * @property integer $currency
 * @property integer $money
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $memo
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
    //积分
    const TYPE_POINT = 4;

    //支付状态
    //未支付
    const STATUS_NOTPAY = 0;
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
            [['payment', 'currency', 'pay_type', 'money'], 'required'],
            ['id', 'unique', 'message' => Yii::t('app', 'This id has already been taken')],
            ['pay_type', 'default', 'value' => self::TYPE_ONLINE],
            ['pay_type', 'in', 'range' => [self::TYPE_ONLINE, self::TYPE_OFFLINE, self::TYPE_RECHARGE, self::TYPE_POINT]],
            ['pay_state', 'default', 'value' => self::STATUS_NOTPAY],
            ['pay_state', 'in', 'range' => [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_REFUND, self::STATUS_NOTPAY, self::STATUS_CLOSED, self::STATUS_REVOKED, self::STATUS_ERROR]],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'money' => Yii::t('payment', 'Money'),
            'payment' => Yii::t('payment', 'Payment method'),
        ];
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

    public static function create($attribute)
    {
        $model = new static ($attribute);
        return $model->save();
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
            $payment->pay_id = $params['pay_id'];
            $payment->pay_state = static::STATUS_SUCCESS;
            $payment->memo = $params['message'];
            $payment->save();
            if ($payment->pay_type == static::TYPE_RECHARGE) {//充值
                Purse::Change($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->payment . '充值');
            } else if ($payment->pay_type == static::TYPE_POINT) {//购买积分
                Purse::Change($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->payment . '充值');
                Purse::Change($payment->user_id, $payment->currency, -$payment->money, 'purchase', '积分购买');
                $point = static::getPoint($payment->money, $payment->currency);
                Purse::Change($payment->user_id, Purse::CURRENCY_POINT, $point, 'purchase', '购买积分' . $point);
            }
            return true;
        }
        return false;
    }
}