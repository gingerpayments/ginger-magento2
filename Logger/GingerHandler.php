<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class GingerHandler extends Base
{

    protected $loggerType = Logger::DEBUG;
    protected $fileName = '/var/log/ginger.log';
}
