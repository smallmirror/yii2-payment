<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;

?>
<div class="row">
    <div class="col-md-2">
        <?= $this->render('@yuncms/user/views/setting/_menu') ?>
    </div>
    <div class="col-md-10">
        <h2 class="h3 post-title"><?= Yii::t('payment', 'Recharge') ?></h2>
        <div class="row">
            <div class="col-md-12">
                <?php $form = ActiveForm::begin([
                    'layout' => 'horizontal',
                    'enableClientValidation' => true
                ]); ?>
                <?= $form->field($model, 'currency')->inline(true)->radioList(['CNY' => '人民币', 'USD' => '美元']); ?>
                <?= $form->field($model, 'money'); ?>
                <?= $form->field($model, 'pay_type')->inline(true)->radioList(['1' => '余额充值', '3' => '充值', '4' => '购买金币']); ?>
                <?= $form->field($model, 'gateway')->inline(true)->radioList(ArrayHelper::map(Yii::$app->getModule('payment')->gateways, 'id', 'title')); ?>
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

