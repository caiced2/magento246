<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy;

use Magento\Framework\App\ObjectManager;

/**
 * Cms Hierarchy Copy Form Container Block
 *
 * @api
 * @since 100.0.2
 */
class Manage extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     * @param \Magento\VersionsCms\Helper\Data|null $versionsCmsHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = [],
        \Magento\VersionsCms\Helper\Data $versionsCmsHelper = null
    ) {
        $data['versionsCmsHelper'] = $versionsCmsHelper ?? ObjectManager::getInstance()
                ->get(\Magento\VersionsCms\Helper\Data::class);
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Retrieve Delete Hierarchies Url
     *
     * @return string
     */
    public function getDeleteHierarchiesUrl()
    {
        return $this->getUrl('adminhtml/*/delete');
    }

    /**
     * Retrieve Copy Hierarchy Url
     *
     * @return string
     */
    public function getCopyHierarchyUrl()
    {
        return $this->getUrl('adminhtml/*/copy');
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return \Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Edit\Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => ['id' => 'manage_form', 'method' => 'post']]);

        $currentWebsite = $this->getRequest()->getParam('website');
        $currentStore = $this->getRequest()->getParam('store');
        $excludeScopes = [];
        if ($currentStore) {
            $storeId = $this->_storeManager->getStore($currentStore)->getId();
            $excludeScopes = [\Magento\VersionsCms\Helper\Hierarchy::SCOPE_PREFIX_STORE . $storeId];
        } elseif ($currentWebsite) {
            $websiteId = $this->_storeManager->getWebsite($currentWebsite)->getId();
            $excludeScopes = [\Magento\VersionsCms\Helper\Hierarchy::SCOPE_PREFIX_WEBSITE . $websiteId];
        }
        $allStoreViews = $currentStore || $currentWebsite;
        $form->addField(
            'scopes',
            'multiselect',
            [
                'name' => 'scopes[]',
                'class' => 'manage-select',
                'title' => __('Manage Hierarchies'),
                'values' => $this->_prepareOptions($allStoreViews, $excludeScopes)
            ]
        );

        if ($currentWebsite) {
            $form->addField('website', 'hidden', ['name' => 'website', 'value' => $currentWebsite]);
        }
        if ($currentStore) {
            $form->addField('store', 'hidden', ['name' => 'store', 'value' => $currentStore]);
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare options for Manage select
     *
     * @param bool $all
     * @param array $excludeScopes
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareOptions($all, $excludeScopes)
    {
        $storeStructure = $this->_systemStore->getStoresStructure($all);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $nonEscapableNbspChar = html_entity_decode('&#160;', ENT_NOQUOTES, 'UTF-8');
        $options = [];

        foreach ($storeStructure as $website) {
            $value = \Magento\VersionsCms\Helper\Hierarchy::SCOPE_PREFIX_WEBSITE . $website['value'];
            if (isset($website['children'])) {
                $website['value'] = in_array($value, $excludeScopes) ? [] : $value;
                $options[] = [
                    'label' => $website['label'],
                    'value' => $website['value'],
                    'style' => 'border-bottom: none; font-weight: bold;',
                ];
                foreach ($website['children'] as $store) {
                    if (isset($store['children']) && !in_array($store['value'], $excludeScopes)) {
                        $storeViewOptions = $this->getStoreViewOptions($store, $excludeScopes, $nonEscapableNbspChar);
                        if ($storeViewOptions) {
                            $options[] = [
                                'label' => str_repeat($nonEscapableNbspChar, 4) . $store['label'],
                                'value' => $storeViewOptions,
                            ];
                        }
                    }
                }
            } elseif ($website['value'] == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                $website['value'] = \Magento\VersionsCms\Helper\Hierarchy::SCOPE_PREFIX_STORE .
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID;
                $options[] = ['label' => $website['label'], 'value' => $website['value']];
            }
        }
        return $options;
    }

    /**
     * Return Store View Options.
     *
     * @param array $store
     * @param array $excludeScopes
     * @param string $nonEscapableNbspChar
     * @return array
     */
    private function getStoreViewOptions(array $store, array $excludeScopes, string $nonEscapableNbspChar): array
    {
        $storeViewOptions = [];

        foreach ($store['children'] as $storeView) {
            $storeView['value'] = \Magento\VersionsCms\Helper\Hierarchy::SCOPE_PREFIX_STORE .
                $storeView['value'];
            if (!in_array($storeView['value'], $excludeScopes)) {
                $storeView['label'] = str_repeat($nonEscapableNbspChar, 4) . $storeView['label'];
                $storeViewOptions[] = $storeView;
            }
        }

        return $storeViewOptions;
    }
}
