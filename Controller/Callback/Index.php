<?php

namespace Satispay\Satispay\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use \Satispay\Satispay\Model\Method\Satispay;
use Satispay\Satispay\Helper\Logger;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use \SatispayGBusiness\Payment;

/**
 * Class Index
 * @package Satispay\Satispay\Controller\Callback
 */
class Index extends Action
{

    const ACCEPTED_STATUS = "ACCEPTED";
    const CANCELED_STATUS = "CANCELED";

    /**
     * @var OrderSender
     */
    protected $orderSender;

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
     * @param Order $order
     * @param OrderSender $orderSender
     * @param Satispay $satispay
     * @param Logger $logger
     * @param Serializer $serializer
     */
    public function __construct(
        Context $context,
        Order $order,
        OrderSender $orderSender,
        Satispay $satispay,
        Logger $logger,
        Serializer $serializer
    )
    {
        parent::__construct($context);
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }


    public function execute()
    {
        try {
            $this->logger->logInfo(__('START satispay/callback/ call'));
            $this->logger->logInfo($this->serializer->serialize($this->getRequest()->getParams()));

            $satispayPayment = Payment::get($this->getRequest()->getParam("payment_id"));
            $order = $this->order->load($satispayPayment->metadata->order_id);

            if ($order->getState() === $order::STATE_NEW) {
                if ($satispayPayment->status === self::ACCEPTED_STATUS) {
                    $this->logger->logInfo(__('Payment received with status %1', self::ACCEPTED_STATUS));
                    $payment = $order->getPayment();
                    $payment->setTransactionId($satispayPayment->id);
                    $payment->setCurrencyCode($satispayPayment->currency);
                    $payment->setIsTransactionClosed(true);
                    $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

                    $order->setState($order::STATE_PROCESSING);
                    $order->setStatus($order::STATE_PROCESSING);
                    $order->save();

                    // Payment is OK: send the new order email
                    if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                    }
                } elseif ($satispayPayment->status === self::CANCELED_STATUS) {
                    $cancelMessage = __('Payment received with status %1.', self::CANCELED_STATUS);
                    $this->logger->logInfo($cancelMessage);
                    $order->registerCancellation($cancelMessage);
                    $order->save();
                }
            }

            $this->logger->logInfo(__('END satispay/callback/ call with OK status'));
            $this->getResponse()->setBody('OK');

        } catch (\Exception $e) {
            $this->logger->logError($e->getMessage());
        }
    }
}
