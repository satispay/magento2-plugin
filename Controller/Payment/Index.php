<?php

namespace Satispay\Satispay\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Satispay\Satispay\Model\Method\Satispay;
use Satispay\Satispay\Helper\Logger;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \SatispayGBusiness\Payment;
use \SatispayGBusiness\Api;

/**
 * Class Index
 * @package Satispay\Satispay\Controller\Payment
 */
class Index extends Action
{

    const LIVE_API_URL_PATH = 'payment/satispay/api_url';
    const SANDBOX_API_URL_PATH = 'payment/satispay/sandbox_api_url';

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Index constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param Satispay $satispay
     * @param Logger $logger
     * @param Serializer $serializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        Satispay $satispay,
        Logger $logger,
        Serializer $serializer,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        try {
            $this->logger->logInfo(__('START satispay/payment/ call'));
            $order = $this->checkoutSession->getLastRealOrder();

            if ($order->getState() == $order::STATE_NEW) {
                $apiData = [
                    "flow" => "MATCH_CODE",
                    "amount_unit" => $this->priceCurrency->roundPrice($order->getGrandTotal()) * 100,
                    "currency" => $order->getOrderCurrencyCode(),
                    "external_code" => $order->getIncrementId(),
                    "callback_url" => $this->_url->getUrl('satispay/callback/', [
                        "_query" => "payment_id={uuid}"
                    ]),
                    "metadata" => [
                        "order_id" => $order->getId(),
                        "redirect_url" => $this->_url->getUrl('satispay/redirect/', [
                            "_query" => "payment_id={uuid}"
                        ])
                    ]
                ];
                $this->logger->logInfo(__('Create payment on satispay via API'));
                $this->logger->logInfo($this->serializer->serialize($apiData));
                $satispayPayment = Payment::create($apiData);

                $satispayUrl = self::LIVE_API_URL_PATH;
                if (Api::getSandbox()) {
                    $satispayUrl = self::SANDBOX_API_URL_PATH;
                }

                $this->_redirect(sprintf('%s/pay/%s', $satispayUrl, $satispayPayment->id));
            }
        } catch (\Exception $e) {
            $this->logger->logError($e->getMessage());
        }
    }
}
