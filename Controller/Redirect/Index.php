<?php
namespace Satispay\Satispay\Controller\Redirect;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Satispay\Satispay\Model\Method\Satispay $satispay
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $satispayPayment = \SatispayGBusiness\Payment::get($this->getRequest()->getParam("payment_id"));

        if ($satispayPayment->status == 'ACCEPTED') {
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
