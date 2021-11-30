<?php

namespace Satispay\Satispay\Model\Method;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Payment\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ProductMetadataInterfaceFactory;
use Satispay\Satispay\Model\Config;
use Satispay\Satispay\Helper\Logger as SatispayLogger;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use \SatispayGBusiness\Api;
use \SatispayGBusiness\Payment;

/**
 * Class Satispay
 * @package Satispay\Satispay\Model\Method
 */
class Satispay extends AbstractMethod
{

    const ACCEPTED_STATUS = "ACCEPTED";
    const CANCELED_STATUS = "CANCELED";
    const PENDING_STATUS = "PENDING";

    /**
     * @var string
     */
    protected $_code = 'satispay';

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var SatispayLogger
     */
    private $satispayLogger;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Satispay constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Config $config
     * @param ProductMetadataInterfaceFactory $productMetadataFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param SatispayLogger $satispayLogger
     * @param Serializer $serializer
     * @param OrderSender $orderSender
     * @param OrderRepositoryInterface $orderRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Config $config,
        ProductMetadataInterfaceFactory $productMetadataFactory,
        PriceCurrencyInterface $priceCurrency,
        SatispayLogger $satispayLogger,
        Serializer $serializer,
        OrderSender $orderSender,
        OrderRepositoryInterface $orderRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->config = $config;
        $this->priceCurrency = $priceCurrency;
        $this->satispayLogger = $satispayLogger;
        $this->serializer = $serializer;
        $this->orderSender = $orderSender;
        $this->orderRepository = $orderRepository;

        Api::setPublicKey($this->config->getPublicKey());
        Api::setPrivateKey($this->config->getPrivateKey());

        if ($this->config->getSandbox()) {
            Api::setKeyId($this->config->getSandboxKeyId());
            Api::setSandbox(true);
        } else {
            Api::setKeyId($this->config->getKeyId());
        }

        $this->productMetadata = $productMetadataFactory->create();
        $version = $this->productMetadata->getVersion();

        Api::setPluginNameHeader('Magento2');
        Api::setPlatformVersionHeader($version);
        Api::setTypeHeader('ECOMMERCE-PLUGIN');
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        try {
            $order = $payment->getOrder();

            $apiData = [
                'flow' => "REFUND",
                'amount_unit' => $this->priceCurrency->roundPrice($amount) * 100,
                'currency' => $order->getOrderCurrencyCode(),
                "parent_payment_uid" => $payment->getParentTransactionId(),
                'description' => '#' . $order->getIncrementId()
            ];
            $this->satispayLogger->logInfo(__('Create refund on satispay via API'));
            $this->satispayLogger->logInfo($this->serializer->serialize($apiData));

            $satispayPayment = Payment::create($apiData);
            $payment->setTransactionId($satispayPayment->id);

        } catch (\Exception $e) {
            $this->satispayLogger->logError($e->getMessage());
        }

        return $this;
    }

    /**
     * @param string $paymentId
     * @return array|mixed
     */
    public function checkPayment($paymentId) {
        $satispayPayment = [];
        try {

            $this->satispayLogger->logInfo(__('Get satispay payment via API: %1', $paymentId));
            $satispayPayment = Payment::get($paymentId);

        } catch (\Exception $e) {
            $this->satispayLogger->logError($e->getMessage());
        }
        return $satispayPayment;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $satispayPayment
     * @throws \Exception
     */
    public function acceptOrder(\Magento\Sales\Model\Order $order, $satispayPayment) {
        $payment = $order->getPayment();
        $payment->setTransactionId($satispayPayment->id);
        $payment->setCurrencyCode($satispayPayment->currency);
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

        $order->setState($order::STATE_PROCESSING);
        $order->setStatus($order::STATE_PROCESSING);
        $this->orderRepository->save($order);

        $this->satispayLogger->logInfo(__('Payment %1 for order %2 accepted', $satispayPayment->id, $order->getIncrementId()));

        // Payment is OK: send the new order email
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelOrder(\Magento\Sales\Model\Order $order, $message) {
        $order->registerCancellation($message);
        $this->orderRepository->save($order);

        $this->satispayLogger->logInfo(__('Order %1 canceled', $order->getIncrementId()));
    }

    /**
     * By returning true, Magento will not send the new order email immediately.
     * This will eventually be done by Satispay during the callback action.
     *
     * @return bool
     */
    public function getOrderPlaceRedirectUrl()
    {
        return true;
    }
}
