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
use yuncms\payment\ModuleTrait;

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
 * @property integer $trade_type
 * @property integer $trade_state
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
    use ModuleTrait;

    //交易类型
    const TYPE_NATIVE = 1;//原生扫码支付
    const TYPE_JS_API = 2;//应用内JS API
    const TYPE_APP = 3;//app支付
    const TYPE_MWEB = 4;//H5支付
    const TYPE_MICROPAY = 5;//刷卡支付
    const TYPE_OFFLINE = 6;//离线（汇款、转账等）支付

    //交易状态
    const STATE_NOT_PAY = 0;//未支付
    const STATE_SUCCESS = 1;//支付成功
    const STATE_FAILED = 2;//支付失败
    const STATE_REFUND = 3;//转入退款
    const STATE_CLOSED = 4;//已关闭
    const STATE_REVOKED = 5;//已撤销
    const STATE_ERROR = 6;//错误

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

            ['trade_type', 'default', 'value' => static::TYPE_NATIVE],
            ['trade_type', 'in', 'range' => [
                static::TYPE_NATIVE,//扫码付款
                static::TYPE_JS_API,//嵌入式 JS SDK付款
                static::TYPE_APP,//APP付款
                static::TYPE_MWEB,//H5 Web 付款
                static::TYPE_MICROPAY,//刷卡付款
                static::TYPE_OFFLINE,//转账汇款
            ]],

            ['trade_state', 'default', 'value' => static::STATE_NOT_PAY],
            ['trade_state', 'in', 'range' => [
                static::STATE_NOT_PAY,
                static::STATE_SUCCESS,
                static::STATE_FAILED,
                static::STATE_REFUND,
                static::STATE_CLOSED,
                static::STATE_REVOKED,
                static::STATE_ERROR,
            ]],
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
            'trade_type' => Yii::t('payment', 'Trade Type'),
            'trade_state' => Yii::t('payment', 'Trade State'),
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
        if (static::STATE_SUCCESS == $payment->trade_state) {
            return true;
        }
        if ($status == true) {
            $payment->updateAttributes([
                'pay_id' => $params['pay_id'],
                'trade_state' => static::STATE_SUCCESS,
                'note' => $params['message']
            ]);
            /** @var \yuncms\payment\OrderInterface $orderModel */
            $orderModel = $payment->model;
            if (!empty($payment->model_id) && !empty($orderModel)) {
                $orderModel::setPayStatus($payment->model_id, $paymentId, $status, $params);
            }
            return true;
        }
        return false;
    }
}