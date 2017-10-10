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
            'id' => $this->string()->notNull()->comment('ID'),
            'model_id' => $this->integer()->comment('Model ID'),
            'model' => $this->string()->comment('Model'),
            'pay_id' => $this->string()->comment('Pay ID'),
            'user_id' => $this->integer()->comment('User ID'),
            'name' => $this->integer()->comment('Payment Name'),
            'gateway' => $this->string(50)->comment('Gateway'),
            'currency' => $this->string(20)->notNull()->comment('Currency'),
            'money' => $this->decimal(10, 2)->notNull()->defaultValue(0.00)->comment('Money'),
            'trade_state' => $this->smallInteger()->notNull()->comment('Trade Type'),
            'trade_type' => $this->smallInteger()->notNull()->comment('Trade State'),
            'ip' => $this->string()->notNull()->comment('Pay IP'),
            'note' => $this->text()->comment('Pay Note'),
            'return_url' => $this->text()->comment('回调URL'),
            'created_at' => $this->integer()->notNull()->comment('Created At'),
            'updated_at' => $this->integer()->notNull()->comment('Updated At'),
        ], $tableOptions);

        $this->addPrimaryKey('PK', '{{%payment}}', 'id');

        $this->createIndex('model_id_model', '{{%payment}}', ['model_id', 'model']);
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
