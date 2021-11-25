<?php

namespace Satispay\Satispay\Controller\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Satispay\Satispay\Model\Method\Satispay;
use Magento\Sales\Api\OrderRepositoryInterface;
use Satispay\Satispay\Helper\Logger;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use \SatispayGBusiness\Payment;

/**
 * Class Index
 * @package Satispay\Satispay\Controller\Redirect
 */
class Index extends Action
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
     * @var Logger
     */
    protected $logger;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * Index constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Satispay $satispay
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param Serializer $serializer
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Satispay $satispay,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        Serializer $serializer
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    public function execute()
    {
        try {
            $this->logger->logInfo(__('START satispay/redirect/ call'));
            $this->logger->logInfo($this->serializer->serialize($this->getRequest()->getParams()));
            $satispayPayment = Payment::get($this->getRequest()->getParam("payment_id"));

            if ($satispayPayment->status == 'ACCEPTED') {

                $this->_redirect('checkout/onepage/success');
            } else {
                $order = $this->checkoutSession->getLastRealOrder();
                $order->registerCancellation(__('Payment has been cancelled.'));
                $this->orderRepository->save($order);
                $this->checkoutSession->restoreQuote();

                $this->_redirect('checkout/cart');
            }

        } catch (\Exception $e) {
            $this->logger->logError($e->getMessage());
        }
    }
}
