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
 * Class OrderItem
 * @property int $id
 * @property int $order_id 订单ID
 *
 *
 * Defined relations:
 * @property Order $order
 *
 * @package yuncms\payment\models
 */
class OrderItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_item}}';
    }

    /**
     * Order Relation
     * @return \yii\db\ActiveQueryInterface
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }


}