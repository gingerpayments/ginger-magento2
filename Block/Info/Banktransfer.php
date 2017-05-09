<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Block\Info;

use Magento\Payment\Block\Info;

class Banktransfer extends Info
{

    protected $_mailingAddress;
    protected $_template = 'Ginger_Payments::info/banktransfer.phtml';

    /**
     *
     * @return string
     */
    public function getMailingAddress()
    {
        if ($this->_mailingAddress === null) {
            $this->_mailingAddress = $this->getInfo()->getAdditionalInformation('mailing_address');
        }
        return $this->_mailingAddress;
    }
}
