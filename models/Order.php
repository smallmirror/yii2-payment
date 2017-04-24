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

/**
 * Class Order
 * @property int $id 订单号
 * @property int $user_id 用户ID
 * @property int $state 订单状态
 * @property int $ip 用户IP
 *
 * @property integer $created_at
 * @property integer $updated_at
 *
 * Defined relations:
 * @property User $user
 * @property Payment $payment
 *
 * Dependencies:
 * @property-read Module $module
 *
 * @package yuncms\payment\models
 */
class Order extends ActiveRecord
{
    //等待付款
    const STATUS_PENDING = 0;

    //支付成功
    const STATUS_SUCCESS = 1;

    //退款
    const STATUS_REFUND = 2;

    //订单关闭
    const STATUS_CLOSED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    /**
     * 生成付款流水号
     */
    public function generateId()
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
     * 设置支付状态
     * @param int $id
     * @param int $status
     * @param array $params
     * @return bool
     */
    public static function setPayStatus($id, $status, $params)
    {
        if (($order = static::findOne($id)) == null) {
            return false;
        }
        if (static::STATUS_SUCCESS == $order->state) {
            return true;
        }
        if ($status == true) {
            $order->updateAttributes(['state' => static::STATUS_SUCCESS]);
            return $order->save();
        }
        return false;
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
                $this->id = $this->generateId();
                $this->user_id = Yii::$app->user->getId();
                $this->ip = Yii::$app->request->userIP;
            }
            return true;
        } else {
            return false;
        }
    }
}