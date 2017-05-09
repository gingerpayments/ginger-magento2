<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Model\Adminhtml\Source;

use Magento\Sales\Model\Config\Source\Order\Status;

class Pending extends Status
{

    protected $_stateStatuses = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
}
