<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ginger\Payments\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Heading extends Field
{

    /**
     * Render block: table heading
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label"></td>';
        $html .= '  <td class="value">';
        $html .= '    <div class="mm-heading-ginger">' . $element->getData('label') . '</div>';
        $html .= '	  <div class="mm-comment-ginger">';
        $html .= '        <div id="content">' . $element->getData('comment') . '</div>';
        $html .= '    </div>';
        $html .= '  </td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
