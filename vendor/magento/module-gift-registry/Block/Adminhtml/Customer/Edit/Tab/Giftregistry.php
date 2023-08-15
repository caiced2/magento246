<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

class Giftregistry extends TabWrapper
{
    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_authorization = $context->getAuthorization();
        $this->_coreRegistry = $registry;
        $this->_giftRegistryData = $giftRegistryData;
        parent::__construct($context, $data);
    }

    /**
     * Set identifier and title
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('gifregustry');
        $this->setTabLabel(__('Gift Registry'));
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function isAjaxLoaded()
    {
        return true;
    }

    /**
     * Check whether tab can be showed
     *
     * @return bool
     */
    public function canShowTab()
    {
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $customerId && $this->_giftRegistryData->isEnabled() && $this->_authorization->isAllowed(
            'Magento_GiftRegistry::customer_magento_giftregistry'
        );
    }

    /**
     * Precessor tab ID getter
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAfter()
    {
        return 'reviews';
    }

    /**
     * Tab class getter
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return 'ajax';
    }

    /**
     * Tab URL getter
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTabUrl()
    {
        return $this->getUrl('adminhtml/giftregistry_customer/grid', ['_current' => true]);
    }
}
