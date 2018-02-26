/*browser:true*/
/*global define*/
define(
  [
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'mage/url'
  ],
  function (ko, $, Component, url) {
    'use strict'
    return Component.extend({
      defaults: {
        template: 'Satispay_Satispay/payment/form',
        redirectAfterPlaceOrder: false
      },
      afterPlaceOrder: function () {
        window.location.replace(url.build('satispay/satispay/payment'));
      }
    })
  }
)
