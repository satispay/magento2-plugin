<?php

namespace Satispay\Satispay\Controller\Payment;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Satispay\Satispay\Model\Method\Satispay $satispay
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getState() == $order::STATE_NEW) {
            $satispayPayment = \SatispayGBusiness\Payment::create([
                "flow" => "MATCH_CODE",
                "amount_unit" => $order->getGrandTotal() * 100,
                "currency" => $order->getOrderCurrencyCode(),
                "external_code" => $order->getIncrementId(),
                "callback_url" => $this->_url->getUrl('satispay/callback/', [
                    "_query" => "payment_id={uuid}"
                ]),
                "metadata" => [
                    "order_id" => $order->getId(),
                    "redirect_url" => $this->_url->getUrl('satispay/redirect/', [
                        "_query" => "payment_id={uuid}"
                    ])
                ]
            ]);

            $satispayUrl = 'https://online.satispay.com';
            if (\SatispayGBusiness\Api::getSandbox()) {
                $satispayUrl = 'https://staging.online.satispay.com';
            }

            $this->_redirect(sprintf('%s/pay/%s', $satispayUrl, $satispayPayment->id));
        }
    }
}
