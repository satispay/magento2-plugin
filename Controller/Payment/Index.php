<?php

namespace Satispay\Satispay\Controller\Payment;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $orderRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Satispay\Satispay\Model\Method\Satispay $satispay,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
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
                "redirect_url" => $this->_url->getUrl('satispay/redirect/'),
                "metadata" => [
                    "order_id" => $order->getId(),
                ]
            ]);
            $payment = $order->getPayment();
            if (isset($payment)) {
                // Set last transition id as the satispay payment id
                $payment->setLastTransId($satispayPayment->id);
                $this->orderRepository->save($order);
            }
            $this->_redirect($satispayPayment->redirect_url);
        }
    }
}
