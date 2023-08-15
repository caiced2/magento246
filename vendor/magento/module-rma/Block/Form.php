<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * RMA Item Dynamic attributes Form Block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block;

class Form extends \Magento\CustomAttributeManagement\Block\Form
{
    /**
     * Name of the block in layout update xml file
     *
     * @var string
     */
    protected $_xmlBlockName = 'magento_rma_item_form_template';

    /**
     * Class path of Form Model
     *
     * @var string
     */
    protected $_formModelPath = \Magento\Rma\Model\Item\Form::class;
}
