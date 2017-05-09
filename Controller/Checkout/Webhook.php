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
use Magento\Framework\Controller\ResultFactory;

class Webhook extends Action
{

    protected $resultFactory;
    private $checkoutSession;
    private $paymentHelper;
    private $gingerModel;
    private $gingerHelper;

    /**
     * Webhook constructor.
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
        $this->resultFactory = $context->getResultFactory();
        $this->gingerModel = $gingerModel;
        $this->gingerHelper = $gingerHelper;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }

    /**
     * Ginger Webhook Controller
     */
    public function execute()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $this->gingerHelper->addTolog('webhook', $input);

        if (!$input) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents('Invalid JSON', true);
            return;
        }

        if (isset($input['order_id'])) {
            $this->gingerModel->processTransaction($input['order_id'], 'webhook');
        }
    }
}
