<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event;

class NewAction extends \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event
{
    /**
     * New event action
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
