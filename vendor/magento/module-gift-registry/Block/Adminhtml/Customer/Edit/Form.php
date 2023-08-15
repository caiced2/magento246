<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer\Edit;

/**
 * @codeCoverageIgnore
 */
class Form extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\GiftRegistry\Model\TypeFactory
     */
    protected $giftRegistryTypeFactory;

    /**
     * @var string
     */
    protected $_template = 'customer/form.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\GiftRegistry\Model\TypeFactory $giftRegistryTypeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\GiftRegistry\Model\TypeFactory $giftRegistryTypeFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerFactory = $customerFactory;
        $this->giftRegistryTypeFactory = $giftRegistryTypeFactory;
        parent::__construct($context, $data);
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild('entity_items', \Magento\GiftRegistry\Block\Adminhtml\Customer\Edit\Items::class);
        $this->addChild('cart_items', \Magento\GiftRegistry\Block\Adminhtml\Customer\Edit\Cart::class);
        $this->addChild('sharing_form', \Magento\GiftRegistry\Block\Adminhtml\Customer\Edit\Sharing::class);
        $this->addChild(
            'update_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Update Items and Quantities'), 'type' => 'submit']
        );

        return parent::_prepareLayout();
    }

    /**
     * Retrieve website name
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_storeManager->getWebsite($this->getEntity()->getWebsiteId())->getName();
    }

    /**
     * Retrieve owner name
     *
     * @return string
     */
    public function getOwnerName()
    {
        $customer = $this->customerFactory->create()->load($this->getEntity()->getCustomerId());

        return $this->escapeHtml($customer->getName());
    }

    /**
     * Retrieve customer edit form url
     *
     * @return string
     */
    public function getOwnerUrl()
    {
        return $this->getUrl('customer/index/edit', ['id' => $this->getEntity()->getCustomerId()]);
    }

    /**
     * Retrieve gift registry type name
     *
     * @return string
     */
    public function getTypeName()
    {
        $type = $this->giftRegistryTypeFactory->create()->load($this->getEntity()->getTypeId());

        return $this->escapeHtml($type->getLabel());
    }

    /**
     * Retrieve escaped entity title
     *
     * @return string
     */
    public function getEntityTitle()
    {
        return $this->escapeHtml($this->getEntity()->getTitle());
    }

    /**
     * Retrieve escaped entity message
     *
     * @return string
     */
    public function getEntityMessage()
    {
        return $this->escapeHtml($this->getEntity()->getMessage());
    }

    /**
     * Retrieve list of registrants
     *
     * @return string
     */
    public function getRegistrants()
    {
        return $this->escapeHtml($this->getEntity()->getRegistrants());
    }

    /**
     * Return gift registry entity object
     *
     * @return \Magento\GiftRegistry\Model\Entity
     */
    public function getEntity()
    {
        return $this->_coreRegistry->registry('current_giftregistry_entity');
    }

    /**
     * Return shipping address
     *
     * @return \Magento\GiftRegistry\Model\Entity
     */
    public function getShippingAddressHtml()
    {
        return $this->getEntity()->getFormatedShippingAddress();
    }

    /**
     * Return gift registry creation data
     *
     * @return \Magento\GiftRegistry\Model\Entity
     */
    public function getCreatedAt()
    {
        return $this->formatDate(
            $this->getEntity()->getCreatedAt(),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    /**
     * Return update items form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('adminhtml/*/update', ['_current' => true]);
    }
}
