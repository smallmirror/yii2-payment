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
 * Payment ActiveRecord .odel
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
 * @package app\modules\payment\models
 */
class Payment extends ActiveRecord
{
    const PAYTYPE_ONLINE = 1;
    const PAYTYPE_OFFLINE = 2;
    const PAYTYPE_RECHARGE = 3;
    const PAYTYPE_POINT = 4;

    //支付状态

    //未支付
    const PAY_NOTPAY = 0;
    //支付成功
    const PAY_SUCCESS = 1;
    //支付失败
    const PAY_FAILED = 2;
    //转入退款
    const PAY_REFUND = 3;
    //已关闭
    const PAY_CLOSED = 4;
    //已撤销
    const PAY_REVOKED = 5;
    //错误
    const PAY_ERROR = 6;

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
        return [TimestampBehavior::className()];
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            [['payment', 'currency', 'pay_type', 'money'], 'required'],
            ['id', 'unique', 'message' => Yii::t('app', 'This id has already been taken')],
            ['pay_type', 'default', 'value' => self::PAYTYPE_ONLINE],
            ['pay_type', 'in', 'range' => [self::PAYTYPE_ONLINE, self::PAYTYPE_OFFLINE, self::PAYTYPE_RECHARGE, self::PAYTYPE_POINT]],
            ['pay_state', 'default', 'value' => self::PAY_NOTPAY],
            ['pay_state', 'in', 'range' => [self::PAY_SUCCESS, self::PAY_FAILED, self::PAY_REFUND, self::PAY_NOTPAY, self::PAY_CLOSED, self::PAY_REVOKED, self::PAY_ERROR]],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return array_merge(parent::attributeHints(), [
            'money' => Yii::t('payment', 'Money'),
            'payment' => Yii::t('payment', 'Payment method'),
        ]);
    }

    /**
     * Generates new ID
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
            $row = (new Query())->from(self::tableName())->where(['id' => $id])->exists();
        } while ($row);
        return $id;
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->ip = long2ip($this->ip);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->id = $this->generatePaymentId();
                $this->user_id = Yii::$app->user->getId();
            }
            //IP转long
            $this->ip = sprintf("%u", ip2long(Yii::$app->request->userIP));
            return true;
        } else {
            return false;
        }
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
        $payment = Payment::findOne(['id' => $paymentId]);
        if ($payment && self::PAY_SUCCESS == $payment->pay_state) {
            return true;
        }
        if ($payment && $status == true) {
            $payment->pay_id = $params['pay_id'];
            $payment->pay_state = self::PAY_SUCCESS;
            $payment->memo = $params['message'];
            $payment->save();
            if ($payment->pay_type == self::PAYTYPE_RECHARGE) {//充值
                Purse::Change($payment->user_id, $payment->currency, $payment->money, 'recharge', $payment->payment . '充值');
            } else if ($payment->pay_type == self::PAYTYPE_POINT) {//购买积分
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