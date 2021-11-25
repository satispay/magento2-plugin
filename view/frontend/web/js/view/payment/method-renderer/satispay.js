/*browser:true*/
/*global define*/
define(["Magento_Checkout/js/view/payment/default", "mage/url"], function (
    Component,
    url
) {
    "use strict";

    var checkoutConfig = window.checkoutConfig.payment;
    console.log(checkoutConfig);

    return Component.extend({
        defaults: {
            template: "Satispay_Satispay/payment/form",
            redirectAfterPlaceOrder: false
        },
        afterPlaceOrder: function () {
            window.location.replace(url.build("satispay/payment/"));
        }
    });
});
