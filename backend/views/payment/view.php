<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yuncms\admin\widgets\Jarvis;

/* @var $this yii\web\View */
/* @var $model yuncms\payment\models\Payment */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('payment', 'Manage Payment'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section id="widget-grid">
    <div class="row">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12 payment-view">
            <?php Jarvis::begin([
                'noPadding' => true,
                'editbutton' => false,
                'deletebutton' => false,
                'header' => Html::encode($this->title),
                'bodyToolbarActions' => [
                    [
                        'label' => Yii::t('payment', 'Manage Payment'),
                        'url' => ['index'],
                    ],
                    [
                        'label' => Yii::t('payment', 'Update Payment'),
                        'url' => ['update', 'id' => $model->id],
                        'options' => ['class' => 'btn btn-primary btn-sm']
                    ],
                    [
                        'label' => Yii::t('payment', 'Delete Payment'),
                        'url' => ['delete', 'id' => $model->id],
                        'options' => [
                            'class' => 'btn btn-danger btn-sm',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]
                    ],
                ]
            ]); ?>
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'order_id',
                    'pay_id',
                    'user_id',
                    'name',
                    'gateway',
                    'currency',
                    'money',
                    'pay_type',
                    'pay_state',
                    'ip',
                    'note:ntext',
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
            <?php Jarvis::end(); ?>
        </article>
    </div>
</section>