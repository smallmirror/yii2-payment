<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yuncms\system\widgets\JsBlock;
?>

<?php
if (isset($res['qrCode'])) {
    ?>
    <div style="text-align:center">
        <img style="margin:5rem 0 0" src="<?= $res['qrCode'] ?>">
        <p style="margin-top:10px">扫码二维码，微信支付</p>
    </div>
    <?php JsBlock::begin() ?>
    <script>
        function getOrderStatus() {
            $.get("<?=Url::to(['/payment/default/query', 'id' => $payment->id]);?>", function (result) {
                if (result.status == 'succ') {
                    window.location.href = "<?=Url::to(['/user/point/index']);?>";
                }
            });
        }
        $(function () {
            setInterval("getOrderStatus()", 3000);//1000为1秒钟
        });
    </script>
    <?php JsBlock::end() ?>
    <?php
} else {
    Yii::$app->request->enableCsrfValidation = false;
    echo Html::beginForm('https://mapi.alipay.com/gateway.do?_input_charset=utf-8', 'POST', ['id' => sha1($this->context->getUniqueId())]) . "\r\n";
    foreach ($res as $key => $val) {
        echo Html::hiddenInput($key, $val) . "\r\n";
    }
    echo Html::submitButton('pay', ['class' => 'btn btn-primary']) . "\r\n";
    echo Html::endForm();
} ?>

