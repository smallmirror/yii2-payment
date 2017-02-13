<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/* @var \yii\web\View $this */
/* @var yuncms\payment\models\Payment $model */
/* @var ActiveForm $form */
?>
<?php $form = ActiveForm::begin(['layout'=>'horizontal', 'enableAjaxValidation' => true, 'enableClientValidation' => false,]); ?>
<fieldset>
    <?= $form->field($model, 'id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'gateway')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'currency')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'money')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pay_type')->textInput() ?>

    <?= $form->field($model, 'pay_state')->textInput() ?>

</fieldset>
<div class="form-actions">
    <div class="row">
        <div class="col-md-12">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>

