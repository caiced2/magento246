<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Customer;

/**
 * @codeCoverageIgnore
 */
class Info extends \Magento\Reward\Controller\Customer
{
    /**
     * Load reward by customer
     *
     * @return \Magento\Reward\Model\Reward
     */
    protected function _getReward()
    {
        $reward = $this->_objectManager->create(
            \Magento\Reward\Model\Reward::class
        )->setCustomer(
            $this->_getCustomer()
        )->setWebsiteId(
            $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getWebsiteId()
        )->loadByCustomer();
        return $reward;
    }

    /**
     * Info Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_coreRegistry->register('current_reward', $this->_getReward());
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Reward Points'));
        $this->_view->renderLayout();
    }
}
