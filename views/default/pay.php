<?php
/**
 * @var \yii\web\View $this
 * @var \yuncms\payment\models\Payment $payment
 */
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;

if (isset($paymentParams['data'])) {
    $this->registerJs('function getPaymentStatus() {jQuery.get("' . Url::to(['/payment/default/query', 'id' => $payment->id]) . '", function (result) {if (result.status == \'success\') {window.location.href = "' . Url::to(['/payment/default/return', 'id' => $payment->id]) . '";}});}', View::POS_BEGIN);
    $this->registerJs('setInterval("getPaymentStatus()", 3000);');
}
?>
<div class="row">
    <div class="col-md-12">
        <?php if (isset($paymentParams['data'])): ?>
            <div class="center-block">
                <p class="text-center"><img class="img-rounded" src="<?= $paymentParams['data'] ?>"></p>
                <p class="text-center"><?= Yii::t('payment', 'Sweep two-dimensional code, pay immediately') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
