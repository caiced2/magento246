<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\GiftCard\Model\Giftcard as GiftcardModel;
use Magento\Store\Model\ScopeInterface;

class Giftcard extends \Magento\Backend\Block\Widget implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'catalog/product/edit/tab/giftcard.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Email config options factory
     *
     * @var \Magento\Config\Model\Config\Source\Email\TemplateFactory
     */
    protected $_templateOptions;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateOptions
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateOptions,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_templateOptions = $templateOptions;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->setData('opened', true);
        return parent::_prepareLayout();
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Gift Card Information');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Gift Card Information');
    }

    /**
     * Check if tab can be displayed
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check if tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Return true when current product is new
     *
     * @return bool
     */
    public function isNew()
    {
        if ($this->_coreRegistry->registry('product')->getId()) {
            return false;
        }
        return true;
    }

    /**
     * Return field name prefix
     *
     * @return string
     */
    public function getFieldPrefix()
    {
        return 'product';
    }

    /**
     * Get current product value, when no value available - return config value
     *
     * @param string $field
     * @return string
     */
    public function getFieldValue($field)
    {
        if (!$this->isNew()) {
            return $this->_coreRegistry->registry('product')->getDataUsingMethod($field);
        }

        return $this->getConfigValue($field);
    }

    /**
     * Return gift card types
     *
     * @return array
     */
    public function getCardTypes()
    {
        return [
            GiftcardModel::TYPE_VIRTUAL => __('Virtual'),
            GiftcardModel::TYPE_PHYSICAL => __('Physical'),
            GiftcardModel::TYPE_COMBINED => __('Combined')
        ];
    }

    /**
     * Return email template select options
     *
     * @return array
     */
    public function getEmailTemplates()
    {
        $result = [];
        $template = $this->_templateOptions->create();
        $template->setPath(GiftcardModel::XML_PATH_EMAIL_TEMPLATE);
        foreach ($template->toOptionArray() as $one) {
            $result[$one['value']] = $this->escapeHtml($one['label']);
        }
        return $result;
    }

    /**
     * @param string $field
     * @return null|string
     */
    public function getConfigValue($field)
    {
        return $this->_scopeConfig->getValue(GiftcardModel::XML_PATH . $field, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check block is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->_coreRegistry->registry('product')->getGiftCardReadonly();
    }

    /**
     * Return 'value-scope' attribute with specified value if is not single store mode
     *
     * @param string $text
     * @return string
     */
    public function getScopeValue($text)
    {
        if ($this->_storeManager->isSingleStoreMode()) {
            return '';
        }
        return 'value-scope="' . __($text) . '"';
    }

    /**
     * Get parent tab code
     *
     * @return string
     */
    public function getParentTab()
    {
        return 'product-details';
    }
}
