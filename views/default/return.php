<?php

use yuncms\payment\models\Payment;

/**
 * @var yuncms\payment\models\Payment $payment
 */
?>

<?php
if ($payment->pay_state == Payment::STATUS_SUCCESS) {
    echo '支付成功';
} else {
    echo '支付不成功';
}
?>
