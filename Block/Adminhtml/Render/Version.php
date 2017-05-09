<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Ginger\Payments\Helper\General as GingerHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends Field
{

    private $gingerHelper;

    /**
     * Version constructor.
     *
     * @param Context      $context
     * @param GingerHelper $gingerHelper
     */
    public function __construct(
        Context $context,
        GingerHelper $gingerHelper
    ) {
        $this->gingerHelper = $gingerHelper;
        parent::__construct($context);
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $this->gingerHelper->getExtensionVersion() . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
