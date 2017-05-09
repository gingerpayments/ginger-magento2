<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Ginger\Payments\Helper\General as GingerHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Redirect extends Action
{

    private $checkoutSession;
    private $paymentHelper;
    private $gingerHelper;

    /**
     * Redirect constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param GingerHelper  $gingerHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        GingerHelper $gingerHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->gingerHelper = $gingerHelper;
        parent::__construct($context);
    }

    /**
     * Ginger Redirect Controller
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $method = $order->getPayment()->getMethod();
        $methodInstance = $this->paymentHelper->getMethodInstance($method);
        if ($methodInstance instanceof \Ginger\Payments\Model\Ginger) {
            $transaction = $methodInstance->startTransaction($order);
            if ($error = $this->gingerHelper->getError($transaction)) {
                $this->messageManager->addError($error);
                $this->gingerHelper->addTolog('error', $error);
                $this->checkoutSession->restoreQuote();
                $this->_redirect('checkout/cart');
            }
            if (isset($transaction['transactions'][0]['payment_url'])) {
                $this->getResponse()->setRedirect($transaction['transactions'][0]['payment_url']);
            }

            $method = $this->gingerHelper->getMethodCode($order);
            if ($method == 'banktransfer') {
                $this->_redirect('ginger/checkout/success', ['order_id' => $order->getGingerTransactionId()]);
            }
        } else {
            $this->messageManager->addError('Unknown Error');
            $this->gingerHelper->addTolog('error', 'Unknown Error');
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
