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
        $order = $this->checkoutSession->getLastRealOrder();

        if (!isset($order) || !$order->getId()) {
            // can't collect order from checkout session, payment is still valid and no need to restore cart
            // can't redirect to success page
            $this->_redirect('checkout/cart');
            return;
        }

        $paymentId = $order->getPayment()->getLastTransId();
        $satispayPayment = \SatispayGBusiness\Payment::get($paymentId);

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
