<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Controller\Checkout;

use Ginger\Payments\Model\Ginger as GingerModel;
use Ginger\Payments\Helper\General as GingerHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Success extends Action
{

    private $checkoutSession;
    private $paymentHelper;
    private $gingerModel;
    private $gingerHelper;

    /**
     * Success constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param GingerModel   $gingerModel
     * @param GingerHelper  $gingerHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        GingerModel $gingerModel,
        GingerHelper $gingerHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->gingerModel = $gingerModel;
        $this->gingerHelper = $gingerHelper;
        parent::__construct($context);
    }

    /**
     * Ginger Success Controller
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['order_id'])) {
            $this->gingerHelper->addTolog('error', __('Invalid return, missing order id.'));
            $this->messageManager->addNoticeMessage(__('Invalid return from Ginger Platform.'));
            $this->_redirect('checkout/cart');
        }

        try {
            $status = $this->gingerModel->processTransaction($params['order_id'], 'success');
        } catch (\Exception $e) {
            $this->gingerHelper->addTolog('error', $e);
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
            $this->_redirect('checkout/cart');
        }

        if (!empty($status['success'])) {
            $this->checkoutSession->start();
            $this->_redirect('checkout/onepage/success?utm_nooverride=1');
        } else {
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__('Something went wrong.'));
            $this->_redirect('checkout/cart');
        }
    }
}
