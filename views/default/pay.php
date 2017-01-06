<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yuncms\system\widgets\JsBlock;

?>

<?php
if (isset($paymentParams['data'])):
    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="text-center">
                <img style="margin:5rem 0 0" src="<?= $paymentParams['data'] ?>">
                <p style="margin-top:10px">扫码二维码，微信支付</p>
            </div>
        </div>
    </div>

    <?php JsBlock::begin() ?>
    <script>
        getPaymentStatus = function () {
            jQuery.get("<?=Url::to(['/payment/default/query', 'id' => $payment->id]);?>", function (result) {
                if (result.status == 'success') {
                    window.location.href = "<?=Url::to(['/payment/default/return', 'id' => $payment->id]);?>";
                }
            });
        }
        setInterval(getPaymentStatus, 3000);//1000为1秒钟
    </script>
    <?php JsBlock::end() ?>
    <?php
endif; ?>

