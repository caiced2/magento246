<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Renderer;

/**
 * Reason field renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Reason extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element
{
    /**
     * Rma reason template name
     *
     * @var string
     */
    protected $_template = 'new/items/renderer/reason.phtml';
}
