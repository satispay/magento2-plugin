/*browser:true*/
/*global define*/
define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    Component,
    rendererList
  ) {
    'use strict';
    rendererList.push(
      {
        type: 'satispay_satispay',
        component: 'Satispay_Satispay/js/view/payment/method-renderer/method'
      }
    )
    return Component.extend({})
  }
)