<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Model\Event as ModelEvent;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Edit extends \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event implements HttpGetActionInterface
{
    /**
     * Edit event action
     *
     * @return void
     */
    public function execute()
    {
        /** @var ModelEvent $event */
        $event = $this->_eventFactory->create()->setStoreId($this->getRequest()->getParam('store', 0));
        $eventId = $this->getRequest()->getParam('id', false);
        if ($eventId) {
            $event->load($eventId);
        } else {
            $event->setCategoryId($this->getRequest()->getParam('category_id'));
        }

        $sessionData = $this->_getSession()->getEventData(true);
        if (!empty($sessionData)) {
            $event->addData($sessionData);
        }

        $this->_coreRegistry->register('magento_catalogevent_event', $event);

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Events'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $event->getId() ? sprintf("#%s", $event->getId()) : __('New Event')
        );
        $layout = $this->_view->getLayout();
        if ($switchBlock = $layout->getBlock('store_switcher')) {
            if (!$event->getId() || $this->_storeManager->isSingleStoreMode()) {
                $layout->unsetChild($layout->getParentName('store_switcher'), 'store_switcher');
            } else {
                $switchBlock->setDefaultStoreName(
                    __('Default Values')
                )->setSwitchUrl(
                    $this->getUrl('adminhtml/*/*', ['_current' => true, 'store' => null])
                );
            }
        }
        $this->_view->renderLayout();
    }
}
