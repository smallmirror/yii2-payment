<?php
/**
 * @var \yii\web\View $this
 * @var \yuncms\payment\models\Payment $payment
 */
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;

?>
<?php
if (isset($paymentParams['data'])) {
    $this->registerJs('function getPaymentStatus() {
            jQuery.get("' . Url::to(['/payment/default/query', 'id' => $payment->id]) . '", function (result) {
                if (result.status == \'success\') {
                    window.location.href = "' . Url::to(['/payment/default/return', 'id' => $payment->id]) . '";
                }
            });
        }',View::POS_BEGIN);
    $this->registerJs('setInterval("getPaymentStatus()", 3000);')
    ?>
    <div style="text-align:center">
        <img style="margin:5rem 0 0" src="<?= $paymentParams['data'] ?>">
        <p style="margin-top:10px"><?= Yii::t('payment', 'Sweep two-dimensional code, pay immediately') ?></p>
    </div>

    <?php
} else {
    Yii::$app->request->enableCsrfValidation = false;
    echo Html::beginForm('https://mapi.alipay.com/gateway.do?_input_charset=utf-8', 'POST', ['id' => sha1($this->context->getUniqueId())]) . "\r\n";
    foreach ($paymentParams as $key => $val) {
        echo Html::hiddenInput($key, $val) . "\r\n";
    }
    echo Html::submitButton('pay', ['class' => 'btn btn-primary']) . "\r\n";
    echo Html::endForm();
} ?>