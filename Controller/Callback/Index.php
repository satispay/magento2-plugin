<?php

namespace Satispay\Satispay\Controller\Callback;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;

    protected $orderSender;

    protected $finalizePaymentService;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Satispay\Satispay\Model\Method\Satispay $satispay,
        \Satispay\Satispay\Model\FinalizePayment $finalizePaymentService
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->finalizePaymentService = $finalizePaymentService;
    }

    public function execute()
    {
        $satispayPayment = \SatispayGBusiness\Payment::get($this->getRequest()->getParam("payment_id"));
        $order = $this->order->load($satispayPayment->metadata->order_id);

        if ($order->getState() == $order::STATE_NEW) {
            $this->finalizePaymentService->finalizePayment($satispayPayment, $order);
        }

        $this->getResponse()->setBody('OK');
    }
}
