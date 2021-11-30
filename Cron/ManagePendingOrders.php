<?php

namespace Satispay\Satispay\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Satispay\Satispay\Model\Method\Satispay;

/**
 * Class ManagePendingOrders
 * @package Satispay\Satispay\Cron
 */
class ManagePendingOrders
{
    /**
     * @var Satispay
     */
    private $satispay;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * ManagePendingOrders constructor.
     * @param Satispay $satispay
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Satispay $satispay,
        OrderCollectionFactory $orderCollectionFactory
    )
    {
        $this->satispay = $satispay;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }


    public function execute()
    {
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', 'pending');

        $orders->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', 'satispay');

        $orders = $orders->getItems();

        foreach ($orders as $order) {
            $satispayPayment = $this->satispay->checkPayment($order->getId());
            switch ($satispayPayment['status']) {
                case  Satispay::ACCEPTED_STATUS:
                    $this->satispay->acceptOrder($order, $satispayPayment);
                    break;
                case  Satispay::CANCELED_STATUS:
                    $cancelMessage = __('Payment received with status %1.', Satispay::CANCELED_STATUS);
                    $this->satispay->cancelOrder($order, $cancelMessage);
                    break;
                case Satispay::PENDING_STATUS:
                    if ($satispayPayment['expired']) {
                        $cancelMessage = __('Payment received with status %1.', Satispay::CANCELED_STATUS);
                        $this->satispay->cancelOrder($order, $cancelMessage);
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
