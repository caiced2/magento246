<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry;

use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Model\Entity;

/**
 * Gift Registry controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Customer extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_GiftRegistry::customer_magento_giftregistry';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * @param string $requestParam
     * @return Entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initEntity($requestParam = 'id')
    {
        $entity = $this->_objectManager->create(\Magento\GiftRegistry\Model\Entity::class);
        $entityId = $this->getRequest()->getParam($requestParam);
        if ($entityId) {
            $entity->load($entityId);
            if (!$entity->getId()) {
                throw new LocalizedException(
                    __('The gift registry entity is incorrect. Verify the entity and try again.')
                );
            }
        }
        $this->_coreRegistry->register('current_giftregistry_entity', $entity);
        return $entity;
    }
}
