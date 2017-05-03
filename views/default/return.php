<?php

use yuncms\payment\models\Payment;

/**
 * @var yuncms\payment\models\Payment $payment
 */
?>
<div class="row">
    <div class="col-md-12">
        <div class="center-block">
            <?php if ($payment->pay_state == Payment::STATUS_SUCCESS): ?>
                <div class="alert alert-success" role="alert">
                    <?=Yii::t('payment', 'Completion of payment.')?>
                    <a href="#" class="alert-link">...</a>
                </div>

                <p class="bg-success">

                    <?php if($payment->pay_type == Payment::TYPE_COIN):?>


                    <?php endif;?>
                    <a href="#" class="alert-link">...</a>
                </p>
            <?php else: ?>
                <div class="alert alert-success" role="alert">
                    <?=Yii::t('payment', 'Completion of payment.')?>
                    <a href="#" class="alert-link">...</a>
                </div>

                <p class="bg-danger"><?=$payment->note?></p>
            <?php endif; ?>
        </div>


        <p>
            <?php
            if ($payment->pay_state == Payment::STATUS_SUCCESS) {
//                if ($payment->pay_type == Payment::TYPE_COIN) {//积分购买
//                    Yii::$app->session->setFlash('success', Yii::t('payment', 'Completion of payment.'));
//                    return $this->redirect(['/user/coin/index']);
//
//                } else if ($payment->pay_type == Payment::TYPE_OFFLINE) {//离线支付
//
//                    Yii::$app->session->setFlash('success', Yii::t('payment', 'Please wait for administrator to confirm.'));
//
//                } else if ($payment->pay_type == Payment::TYPE_ONLINE) {//在线支付
//
//                    Yii::$app->session->setFlash('success', Yii::t('payment', 'Completion of payment.'));
//
//                } else if ($payment->pay_type == Payment::TYPE_RECHARGE) {//充值
//
//                    Yii::$app->session->setFlash('success', Yii::t('payment', 'Completion of payment.'));
//                    return $this->redirect(['/wallet/wallet/index']);
//                }
            } else {
                Yii::$app->session->setFlash('success', $payment->note);
            }


            ?>
        </p>

    </div>
</div>

