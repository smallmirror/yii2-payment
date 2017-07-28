/**
 * Created by xutongle on 2017/6/7.
 */

function isWechat(){
    return navigator.userAgent.indexOf('MicroMessenger') > -1
}

/**
 * 查询支付状态
 * @param pay_id
 */
function getPaymentStatus(pay_id) {
    jQuery.get("/payment/default/query?id=" + pay_id, function (result) {
        if (result.status == 'success') {
            window.location.href = "/payment/default/return?id=" + pay_id;
        }
    });
}