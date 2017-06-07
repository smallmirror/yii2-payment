<?php

use yii\helpers\Html;
use xutl\inspinia\ActiveForm;

/* @var $this yii\web\View */
/* @var $model yuncms\payment\backend\models\PaymentSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="payment-search pull-right">

    <?php $form = ActiveForm::begin([
        'layout' => 'inline',
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', [
        'inputOptions' => [
            'placeholder' => $model->getAttributeLabel('id'),
        ],
    ]) ?>

    <?= $form->field($model, 'model_id', [
        'inputOptions' => [
            'placeholder' => $model->getAttributeLabel('model_id'),
        ],
    ]) ?>

    <?= $form->field($model, 'pay_id', [
        'inputOptions' => [
            'placeholder' => $model->getAttributeLabel('pay_id'),
        ],
    ]) ?>

    <?= $form->field($model, 'user_id', [
        'inputOptions' => [
            'placeholder' => $model->getAttributeLabel('user_id'),
        ],
    ]) ?>

<!--    --><?//= $form->field($model, 'name', [
//        'inputOptions' => [
//            'placeholder' => $model->getAttributeLabel('name'),
//        ],
//    ]) ?>

    <?php // echo $form->field($model, 'gateway') ?>

    <?php // echo $form->field($model, 'currency') ?>

    <?php // echo $form->field($model, 'money') ?>

    <?php // echo $form->field($model, 'trade_type') ?>

    <?php // echo $form->field($model, 'trade_state') ?>

    <?php // echo $form->field($model, 'ip') ?>

    <?php // echo $form->field($model, 'note') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
