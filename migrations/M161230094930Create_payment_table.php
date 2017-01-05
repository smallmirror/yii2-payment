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
            'order_id' => $this->string(),
            'pay_id' => $this->string(),
            'user_id' => $this->integer(),
            'name' => $this->integer(),
            'gateway' => $this->string(50)->notNull()->comment('支付网关'),
            'currency' => $this->string(20)->notNull()->comment('支付币种'),
            'money' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('支付金额'),
            'pay_type' => $this->smallInteger()->notNull()->comment('付款类型'),
            'pay_state' => $this->smallInteger()->notNull(),
            'ip' => $this->string()->notNull()->comment('付款IP'),
            'note' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
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
