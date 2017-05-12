<?php
/**
 * @var \yii\web\View $this
 * @var \yuncms\payment\models\Payment $payment
 */
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;

$js = 'function getPaymentStatus() {jQuery.get("' . Url::to(['/payment/default/query', 'id' => $payment->id]) . '", function (result) {if (result.status == \'success\') {window.location.href = "' . Url::to(['/payment/default/return', 'id' => $payment->id]) . '";}});}';
$js .= 'setInterval("getPaymentStatus()", 3000);';
if (!Yii::$app->request->isAjax && isset($paymentParams['data'])) {
    $this->registerJs($js, View::POS_BEGIN);
} else {
    echo '<script type="text/javascript">' . $js . '</script>';
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
