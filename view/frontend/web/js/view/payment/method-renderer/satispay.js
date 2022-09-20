/*browser:true*/
/*global define*/
define(["Magento_Checkout/js/view/payment/default", "mage/url"], function(
  Component,
  url
) {
  "use strict";

  var checkoutConfig = window.checkoutConfig.payment;
  return Component.extend({
      defaults: {
          logo: 'Satispay_Satispay/images/satispay.png',
          template: "Satispay_Satispay/payment/form",
          redirectAfterPlaceOrder: false
      },
      /**
       * Returns payment logo image
       * @returns {String}
       */
      getPaymentLogoImage: function () {
          return require.toUrl(this.logo);
      },

      afterPlaceOrder: function() {
          window.location.replace(url.build("satispay/payment/"));
      }
  });
});
