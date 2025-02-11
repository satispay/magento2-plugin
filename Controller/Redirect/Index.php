<?php

namespace Satispay\Satispay\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Satispay\Satispay\Model\Method\Satispay;
use SatispayGBusiness\Payment;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Satispay $satispay,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
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
        $satispayPayment = Payment::get($paymentId);

        if ($satispayPayment->status == 'ACCEPTED') {
            $this->_redirect('checkout/onepage/success');
            return;
        }

        if ($satispayPayment->status == 'PENDING') {

            $satispayCancel = Payment::update($paymentId, [
                'action' => 'CANCEL',
            ]);


            if ($satispayCancel->status === 'CANCELED') {
                $order->registerCancellation(__('Payment has been cancelled.'));
                $this->orderRepository->save($order);
                $this->checkoutSession->restoreQuote();
            } else {
                $this->messageManager->addWarningMessage(__('Payment is pending.'));
            }

            $this->_redirect('checkout/cart');

            return;
        }

        $order->registerCancellation(__('Payment has been cancelled.'));
        $this->orderRepository->save($order);
        $this->checkoutSession->restoreQuote();
        $this->messageManager->addWarningMessage(__('Payment has been cancelled.'));
        $this->_redirect('checkout/cart');
    }
}

