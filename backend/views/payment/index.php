<?php
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use xutl\inspinia\Box;
use xutl\inspinia\Toolbar;
use xutl\inspinia\Alert;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yuncms\payment\models\Payment;

/* @var $this yii\web\View */
/* @var $searchModel yuncms\payment\backend\models\PaymentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('payment', 'Manage Payment');
$this->params['breadcrumbs'][] = $this->title;
$this->registerJs("jQuery(\"#batch_deletion\").on(\"click\", function () {
    yii.confirm('" . Yii::t('app', 'Are you sure you want to delete this item?') . "',function(){
        var ids = jQuery('#gridview').yiiGridView(\"getSelectedRows\");
        jQuery.post(\"/payment/payment/batch-delete\",{ids:ids});
    });
});", View::POS_LOAD);
?>
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12 payment-index">
            <?= Alert::widget() ?>
            <?php Pjax::begin(); ?>
            <?php Box::begin([
                'header' => Html::encode($this->title),
            ]); ?>
            <div class="row">
                <div class="col-sm-4 m-b-xs">
                    <?= Toolbar::widget(['items' => [
                        [
                            'label' => Yii::t('payment', 'Manage Payment'),
                            'url' => ['index'],
                        ],
                        [
                            'options' => ['id' => 'batch_deletion', 'class' => 'btn btn-sm btn-danger'],
                            'label' => Yii::t('payment', 'Batch Deletion'),
                            'url' => 'javascript:void(0);',
                        ]
                    ]]); ?>
                </div>
                <div class="col-sm-8 m-b-xs">
                    <?php echo $this->render('_search', ['model' => $searchModel]); ?>
                </div>
            </div>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'options' => ['id' => 'gridview'],
                'layout' => "{items}\n{summary}\n{pager}",
                //'filterModel' => $searchModel,
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',
                        "name" => "id",
                    ],
                    //['class' => 'yii\grid\SerialColumn'],
                    'id',
                    'model_id',
                    'pay_id',
                    'user_id',
                    'user.nickname',
                    'name',
                    'gateway',
                    'currency',
                    'money',
                    [
                        'header' => Yii::t('payment', 'Trade Type'),
                        'value' => function ($model) {
                            if ($model->trade_type == Payment::TYPE_NATIVE) {
                                return Yii::t('payment', 'Native Payment');
                            } else if ($model->trade_type == Payment::TYPE_MWEB) {
                                return Yii::t('payment', 'Mweb Payment');
                            } else if ($model->trade_type == Payment::TYPE_APP) {
                                return Yii::t('payment', 'App Payment');
                            } else if ($model->trade_type == Payment::TYPE_JS_API) {
                                return Yii::t('payment', 'Jsapi Payment');
                            } else if ($model->trade_type == Payment::TYPE_MICROPAY) {
                                return Yii::t('payment', 'Micro Payment');
                            } else if ($model->trade_type == Payment::TYPE_OFFLINE) {
                                return Yii::t('payment', 'Office Payment');
                            }
                        },
                        'format' => 'raw'
                    ],
                    [
                        'header' => Yii::t('payment', 'Trade State'),
                        'value' => function ($model) {
                            if ($model->trade_state == Payment::STATE_NOT_PAY) {
                                return Yii::t('payment', 'State Not Pay');
                            } else if ($model->trade_state == Payment::STATE_SUCCESS) {
                                return Yii::t('payment', 'State Success');
                            } else if ($model->trade_state == Payment::STATE_FAILED) {
                                return Yii::t('payment', 'State Failed');
                            } else if ($model->trade_state == Payment::STATE_REFUND) {
                                return Yii::t('payment', 'State Refund');
                            } else if ($model->trade_state == Payment::STATE_CLOSED) {
                                return Yii::t('payment', 'State Close');
                            } else if ($model->trade_state == Payment::STATE_REVOKED) {
                                return Yii::t('payment', 'State Revoked');
                            } else if ($model->trade_state == Payment::STATE_ERROR) {
                                return Yii::t('payment', 'State Error');
                            }
                        },
                        'format' => 'raw'
                    ],
                    'ip',
                    'note:ntext',
                    'created_at:datetime',
                    'updated_at:datetime',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '{view} {update} {delete}',
                        //'buttons' => [
                        //    'update' => function ($url, $model, $key) {
                        //        return $model->status === 'editable' ? Html::a('Update', $url) : '';
                        //    },
                        //],
                    ],
                ],
            ]); ?>
            <?php Box::end(); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
