<?php

namespace Satispay\Satispay\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Satispay\Satispay\Model\FinalizePayment;
use Satispay\Satispay\Model\Method\Satispay;
use SatispayGBusiness\Payment;

class Index extends Action
{
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var FinalizePayment
     */
    protected $finalizePaymentService;
    /**
     * @var Order
     */
    protected $order;

    /**
     * @param Context $context
     * @param Order $order
     * @param OrderSender $orderSender
     * @param Satispay $satispay
     * @param FinalizePayment $finalizePaymentService
     */
    public function __construct(
        Context $context,
        Order $order,
        OrderSender $orderSender,
        Satispay $satispay,
        FinalizePayment $finalizePaymentService
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->finalizePaymentService = $finalizePaymentService;
    }

    public function execute()
    {
        $satispayPayment = Payment::get($this->getRequest()->getParam("payment_id"));
        $order = $this->order->load($satispayPayment->metadata->order_id);

        if ($order->getState() == $order::STATE_NEW || $order->getState() == $order::STATE_PENDING_PAYMENT) {
            $this->finalizePaymentService->finalizePayment($satispayPayment, $order);
        }

        $this->getResponse()->setBody('OK');
    }
}
