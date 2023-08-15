<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer;

/**
 * Customer giftregistry list block
 *
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Directory\Block\Data
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\GiftRegistry\Model\TypeFactory
     */
    protected $typeFactory;

    /**
     * Template container
     *
     * @var array
     */
    protected $_inputTemplates = [];

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\GiftRegistry\Model\TypeFactory $typeFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\GiftRegistry\Model\TypeFactory $typeFactory,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->customerSession = $customerSession;
        $this->typeFactory = $typeFactory;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * Return edit form header
     *
     * @return \Magento\Framework\Phrase
     */
    public function getFormHeader()
    {
        if ($this->_registry->registry('magento_giftregistry_entity')->getId()) {
            return __('Edit Gift Registry');
        } else {
            return __('Create Gift Registry');
        }
    }

    /**
     * Getter for post data, stored in session
     *
     * @return array|null
     * @codeCoverageIgnore
     */
    public function getFormDataPost()
    {
        return $this->customerSession->getGiftRegistryEntityFormData(true);
    }

    /**
     * Get array of reordered custom registry attributes
     *
     * @return array
     */
    public function getGroupedRegistryAttributes()
    {
        $attributes = $this->getEntity()->getCustomAttributes();
        return empty($attributes['registry']) ? [] : $this->_groupAttributes($attributes['registry']);
    }

    /**
     * Get array of reordered custom registrant attributes
     *
     * @return array
     */
    public function getGroupedRegistrantAttributes()
    {
        $attributes = $this->getEntity()->getCustomAttributes();
        return empty($attributes['registrant']) ? [] : $this->_groupAttributes($attributes['registrant']);
    }

    /**
     * Fetches type list array
     *
     * @return array
     */
    public function getTypeList()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $collection = $this->typeFactory->create()->getCollection()->addStoreData(
            $storeId
        )->applyListedFilter()->applySortOrder();
        $list = $collection->toOptionArray();
        return $list;
    }

    /**
     * Return "create giftregistry" form Add url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddActionUrl()
    {
        return $this->getUrl('magento_giftregistry/index/edit');
    }

    /**
     * Return form back link url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getBackUrl()
    {
        return $this->getUrl('magento_giftregistry');
    }

    /**
     * Return "create giftregistry" form AddPost url
     *
     * @return string
     * @deprecated 101.1.3 Action does not exist
     * @codeCoverageIgnore
     */
    public function getAddPostActionUrl()
    {
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        return '';
    }

    /**
     * Return "create giftregistry" form url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddGiftRegistryUrl()
    {
        return $this->getUrl('magento_giftregistry/index/addselect');
    }

    /**
     * Return "create giftregistry" form url
     *
     * @return string
     * @deprecated 101.1.3 Action does not exist
     * @codeCoverageIgnore
     */
    public function getSaveActionUrl()
    {
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        return '';
    }

    /**
     * Setup template from template file as $_inputTemplates['type'] for specified type
     *
     * @param string $type
     * @param string $template
     * @return $this
     */
    public function addInputTypeTemplate($type, $template)
    {
        $params = ['_relative' => true];
        $area = $this->getArea();
        if ($area) {
            $params['area'] = $area;
        }
        $templateName = $this->resolver->getTemplateFileName($template, $params);

        $this->_inputTemplates[$type] = $templateName;
        return $this;
    }

    /**
     * Return presetted template by type
     *
     * @param string $type
     * @return string
     */
    public function getInputTypeTemplate($type)
    {
        if (isset($this->_inputTemplates[$type])) {
            return $this->_inputTemplates[$type];
        }
        return false;
    }
}
