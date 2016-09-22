<?php

use app\modules\payment\models\Payment;

/**
 * @var app\modules\payment\models\Payment $payment
 */
?>

<?php
if ($payment->pay_state == Payment::PAY_SUCCESS) {
    echo '支付成功';
} else {
    echo '支付不成功';
}
?>
