<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin([
    'id' => 'profile-form',
    'options' => ['class' => 'form-horizontal'],
    'fieldConfig' => [
        'template' => "{label}\n<div class=\"col-lg-9\">{input}</div>\n<div class=\"col-sm-offset-4 col-lg-9\">{error}\n{hint}</div>",
        'labelOptions' => ['class' => 'col-lg-2 control-label'],
    ],
    'enableClientValidation' => true,
    'validateOnBlur' => false,
]); ?>
<?= $form->field($model, 'currency')->inline(true)->radioList(['CNY' => '人民币', 'USD' => '美元']); ?>
<?= $form->field($model, 'money')->inline(true)->radioList(['0.01' => '0.01元', '100' => '100元', '200' => '200元', '500' => '500元']); ?>
<?= $form->field($model, 'pay_type')->inline(true)->radioList(['1' => '下载源码', '3' => '充值', '4' => '购买积分']); ?>
<?= $form->field($model, 'payment')->inline(true)->radioList(['wechat' => '微信支付', 'alipay' => '支付宝', 'paypal' => 'PayPal']); ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-9 u-sub">
            <?= Html::submitButton(Yii::t('app', 'Submit'), ['class' => 'btn btn-primary btn-block']) ?>
            <br>
        </div>
    </div>
<?php ActiveForm::end(); ?>