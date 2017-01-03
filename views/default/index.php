<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
?>
<div class="row">
    <div class="col-md-2">
        <?= $this->render('@yuncms/user/views/setting/_menu') ?>
    </div>
    <div class="col-md-10">
        <h2 class="h3 post-title"><?= Yii::t('payment', 'Recharge') ?></h2>
        <div class="row">
            <div class="col-md-8">
                <?php $form = ActiveForm::begin([
                    'layout' => 'horizontal',
                    'enableClientValidation' => true
                ]); ?>
                <?= $form->field($model, 'currency')->inline(true)->radioList(['CNY' => '人民币', 'USD' => '美元']); ?>
                <?= $form->field($model, 'money')->inline(true)->radioList(['0.01' => '0.01元', '100' => '100元', '200' => '200元', '500' => '500元']); ?>
                <?= $form->field($model, 'payment')->inline(true)->radioList(['wechat' => '微信支付', 'alipay' => '支付宝', 'paypal' => 'PayPal']); ?>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-9">
                        <?= Html::submitButton(Yii::t('app', 'Submit'), ['class' => 'btn btn-block btn-success']) ?>
                        <br>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

