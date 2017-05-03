<?php

namespace yuncms\payment\migrations;

use yii\db\Migration;

class M161230094930Create_payment_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%payment}}', [
            'id' => $this->string()->notNull(),
            'model_id' => $this->integer()->comment('订单ID'),
            'model' => $this->string()->comment('订单模型'),
            'pay_id' => $this->string()->comment('支付号'),
            'user_id' => $this->integer()->comment('用户ID'),
            'name' => $this->integer(),
            'gateway' => $this->string(50)->notNull()->comment('支付网关'),
            'currency' => $this->string(20)->notNull()->comment('支付币种'),
            'money' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('支付金额'),
            'pay_type' => $this->smallInteger()->notNull()->comment('付款类型'),
            'pay_state' => $this->smallInteger()->notNull(),
            'ip' => $this->string()->notNull()->comment('付款IP'),
            'note' => $this->text()->comment('注释'),
            'created_at' => $this->integer()->notNull()->comment('创建时间'),
            'updated_at' => $this->integer()->notNull()->comment('更新时间'),
        ], $tableOptions);

        $this->addPrimaryKey('PK', '{{%payment}}', 'id');
    }

    public function down()
    {
        $this->dropTable('{{%payment}}');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
