<?php

namespace Satispay\Satispay\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use \Satispay\Satispay\Model\Method\Satispay;
use Satispay\Satispay\Helper\Logger;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Sales\Api\OrderRepositoryInterface;
use \SatispayGBusiness\Payment;

/**
 * Class Index
 * @package Satispay\Satispay\Controller\Callback
 */
class Index extends Action
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var Satispay
     */
    protected $satispay;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Index constructor.
     * @param Context $context
     * @param Order $order
     * @param Satispay $satispay
     * @param Logger $logger
     * @param Serializer $serializer
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        Order $order,
        Satispay $satispay,
        Logger $logger,
        Serializer $serializer,
        OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->satispay = $satispay;
        $this->orderRepository = $orderRepository;
    }


    public function execute()
    {
        try {
            $this->logger->logInfo(__('START satispay/callback/ call'));
            $this->logger->logInfo($this->serializer->serialize($this->getRequest()->getParams()));

            $satispayPayment = Payment::get($this->getRequest()->getParam("payment_id"));
            $order = $this->orderRepository->get($satispayPayment->metadata->order_id);

            if ($order->getState() === $order::STATE_NEW) {
                if ($satispayPayment->status === Satispay::ACCEPTED_STATUS) {
                    $this->logger->logInfo(__('Payment received with status %1', Satispay::ACCEPTED_STATUS));
                    $this->satispay->acceptOrder($order, $satispayPayment);
                } elseif ($satispayPayment->status === Satispay::CANCELED_STATUS) {
                    $cancelMessage = __('Payment received with status %1.', Satispay::CANCELED_STATUS);
                    $this->logger->logInfo($cancelMessage);
                    $this->satispay->cancelOrder($order, $cancelMessage);
                }
            }

            $this->logger->logInfo(__('END satispay/callback/ call with OK status'));
            $this->getResponse()->setBody('OK');

        } catch (\Exception $e) {
            $this->logger->logError($e->getMessage());
        }
    }
}
