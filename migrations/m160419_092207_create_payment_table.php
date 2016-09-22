<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
use yii\db\Migration;

class m160419_092207_create_payment_table extends Migration
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
            'gateway_id' => $this->integer()->notNull(),
            'currency' => $this->string()->notNull(),
            'money' => $this->decimal(10, 8)->notNull()->defaultValue(0.00),
            'pay_type' => $this->smallInteger()->notNull(),
            'pay_state' => $this->smallInteger()->notNull(),
            'ip' => $this->string()->notNull(),
            'note' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'expiration_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('', '{{%payment}}', 'id');
    }

    public function down()
    {
        $this->dropTable('{{%payment}}');
    }

}
