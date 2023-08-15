<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Model\Event as ModelEvent;

class Delete extends \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event
{
    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        /** @var ModelEvent $event */
        $event = $this->_eventFactory->create();
        $event->load($this->getRequest()->getParam('id', false));
        if ($event->getId()) {
            try {
                $event->delete();
                $this->messageManager->addSuccess(__('You deleted the event.'));
                if ($this->getRequest()->getParam('category')) {
                    $this->_redirect('adminhtml/category/edit', ['id' => $event->getCategoryId(), 'clear' => 1]);
                } else {
                    $this->_redirect('adminhtml/*/');
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/*/edit', ['_current' => true]);
            }
        }
    }
}
