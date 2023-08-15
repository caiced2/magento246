<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\View;

class Index extends \Magento\GiftRegistry\Controller\View
{
    /**
     * View giftregistry list in 'My Account' section
     *
     * @return void
     */
    public function execute()
    {
        $entity = $this->_objectManager->create(\Magento\GiftRegistry\Model\Entity::class);
        $entity->loadByUrlKey($this->getRequest()->getParam('id'));
        if (!$entity->getId() || !$entity->getCustomerId() || !$entity->getTypeId() || !$entity->getIsActive()) {
            $this->_forward('noroute');
            return;
        }

        /** @var \Magento\Customer\Model\Customer */
        $customer = $this->_objectManager->create(\Magento\Customer\Model\Customer::class);
        $customer->load($entity->getCustomerId());
        $entity->setCustomer($customer);
        $this->_coreRegistry->register('current_entity', $entity);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Gift Registry Info'));
        $this->_view->renderLayout();
    }
}
