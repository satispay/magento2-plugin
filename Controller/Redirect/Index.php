<?php

namespace Satispay\Satispay\Controller\Redirect;


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
        $satispayPayment = \SatispayGBusiness\Payment::get($this->getRequest()->getParam("payment_id"));

        // Set last transition id as the satispay payment id
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        $payment->setLastTransId($satispayPayment->id);
        $this->orderRepository->save($order);

        if ($satispayPayment->status == 'ACCEPTED') {
            $this->_redirect('checkout/onepage/success');
        } else {
            $order->registerCancellation(__('Payment has been cancelled.'));
            $this->orderRepository->save($order);
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
