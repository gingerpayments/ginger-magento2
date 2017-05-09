<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Ginger\Payments\Helper\General as GingerHelper;

class Success extends Template
{

    private $salesFactory;
    private $checkoutSession;
    private $orderFactory;
    private $storeManager;
    private $gingerHelper;

    /**
     * Success constructor.
     *
     * @param Context               $context
     * @param Order                 $salesOrderFactory
     * @param Session               $checkoutSession
     * @param OrderFactory          $orderFactory
     * @param StoreManagerInterface $storeManager
     * @param GingerHelper          $gingerHelper
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Order $salesOrderFactory,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        StoreManagerInterface $storeManager,
        GingerHelper $gingerHelper,
        array $data = []
    ) {
        $this->salesFactory = $salesOrderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->storeManager = $storeManager;
        $this->gingerHelper = $gingerHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function getMailingAddress()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);
        $payment = $order->getPayment();

        if ($payment->getMethod() == 'ginger_methods_banktransfer') {
            return $payment->getAdditionalInformation('mailing_address');
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getCompanyName()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->gingerHelper->getCompanyName($storeId);
    }
}
