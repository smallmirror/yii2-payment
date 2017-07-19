<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
?>
<div class="row">
    <div class="col-md-12">
        <h2 class="h3 profile-title"><?= Yii::t('payment', 'Payment') ?></h2>
        <div class="row">
            <div class="col-md-12">
                <?php $form = ActiveForm::begin([
                    'layout' => 'horizontal',
                    'enableClientValidation' => true
                ]); ?>
                <?= $form->field($model, 'currency')->inline(true)->radioList(['CNY' => '人民币', 'USD' => '美元']); ?>
                <?= $form->field($model, 'money'); ?>
                <?= $form->field($model, 'trade_type')->inline(true)->radioList(['1' => '订单支付', '3' => '充值', '4' => '购买金币']); ?>
                <?= $form->field($model, 'gateway')->inline(true)->radioList(ArrayHelper::map(Yii::$app->getModule('payment')->gateways, 'id', 'title')); ?>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-9">
                        <?= Html::submitButton(Yii::t('payment', 'Payment'), ['class' => 'btn btn-success']) ?>
                        <br>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

