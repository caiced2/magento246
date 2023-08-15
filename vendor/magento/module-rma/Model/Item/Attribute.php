<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Item;

/**
 * RMA Item attribute model
 *
 * @api
 * @method \Magento\Eav\Api\Data\AttributeExtensionInterface getExtensionAttributes()
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Attribute extends \Magento\Eav\Model\Attribute
{
    /**
     * Name of the module
     */
    const MODULE_NAME = 'Magento_Rma';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magento_rma_item_entity_attribute';

    /**
     * Prefix of model events object
     *
     * @var string
     */
    protected $_eventObject = 'attribute';

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Rma\Model\ResourceModel\Item\Attribute::class);
    }
}
