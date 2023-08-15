<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\Banner;

use Magento\Store\Model\Store;

class Processor
{
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    private $salesRuleCollectionFactory;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Grid\CollectionFactory
     */
    private $catalogRuleCollectionFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    private $bannerResourceModel;

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory
     * @param \Magento\CatalogRule\Model\ResourceModel\Grid\CollectionFactory $catalogRuleCollectionFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResourceModel
     */
    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory,
        \Magento\CatalogRule\Model\ResourceModel\Grid\CollectionFactory $catalogRuleCollectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Banner\Model\ResourceModel\Banner $bannerResourceModel
    ) {
        $this->salesRuleCollectionFactory = $salesRuleCollectionFactory->create();
        $this->catalogRuleCollectionFactory = $catalogRuleCollectionFactory->create();
        $this->eventManager = $eventManager;
        $this->bannerResourceModel = $bannerResourceModel;
    }

    /**
     * Modify data for the banner page
     *
     * @param \Magento\Banner\Model\Banner $banner
     * @param integer $storeId
     * @return array
     */
    public function processData($banner, $storeId)
    {
        $this->eventManager->dispatch(
            'adminhtml_banner_edit_tab_content_before_prepare_form',
            ['model' => $banner]
        );

        $banner->setStoreId($storeId);

        $relatedSalesRules = $banner->getRelatedSalesRule();
        if (!empty($relatedSalesRules)) {
            $rulesCollection = $this->salesRuleCollectionFactory->addFieldToFilter(
                'rule_id',
                ['in' => array_values($relatedSalesRules)]
            );
            $this->processRuleData(
                $banner,
                $rulesCollection,
                'banner_sales_rules',
                'sales_rule_listing'
            );
        }

        $relatedCatalogRules = $banner->getRelatedCatalogRule();
        if (!empty($relatedCatalogRules)) {
            $rulesCollection = $this->catalogRuleCollectionFactory->addFieldToFilter(
                'rule_id',
                ['in' => array_values($relatedCatalogRules)]
            );
            $this->processRuleData(
                $banner,
                $rulesCollection,
                'banner_catalog_rules',
                'banner_catalog_rule_listing'
            );
        }

        $this->modifyContentDataByStore($banner, (int)$storeId);

        $bannerData = $banner->getData();
        if (isset($bannerData['related_catalog_rule'])) {
            unset($bannerData['related_catalog_rule']);
        }
        if (isset($bannerData['related_sales_rule'])) {
            unset($bannerData['related_sales_rule']);
        }

        return $bannerData;
    }

    /**
     * Modify data for related rules in related promotion section
     *
     * @param \Magento\Banner\Model\Banner $banner
     * @param $rulesCollection
     * @param string $dataProviderName
     * @param string $listingName
     * @return \Magento\Banner\Model\Banner
     */
    private function processRuleData($banner, $rulesCollection, $dataProviderName, $listingName)
    {
        $rulesData = [];
        /** @var \Magento\CatalogRule\Model\Rule $rule */
        foreach ($rulesCollection->getItems() as $rule) {
            $rulesData[] = $this->getRuleDataArray($rule);
        }
        $banner->setData($dataProviderName, $rulesData);
        $banner->setData($listingName, $rulesData);

        return $banner;
    }

    /**
     * Returns data to fill dynamic row grid
     *
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @return array
     */
    private function getRuleDataArray($rule)
    {
        return [
            'rule_id' => $rule->getRuleId(),
            'name' => $rule->getName(),
            'from_date' => $rule->getFromDate(),
            'to_date' => $rule->getToDate(),
            'is_active' => $rule->getIsActive() ? __('Active') : __('Inactive')
        ];
    }

    /**
     * Modify data in the content section
     *
     * @param \Magento\Banner\Model\Banner $banner
     * @param integer $storeId
     * @return \Magento\Banner\Model\Banner
     */
    private function modifyContentDataByStore(\Magento\Banner\Model\Banner $banner, $storeId)
    {
        $readonly = (bool)$banner->getIsReadonly();
        $saveAllContent = $banner->getCanSaveAllStoreViewsContent();

        $storeContentsData = $this->bannerResourceModel->getStoreContents($banner->getId());
        $defaultContents = isset($storeContentsData[Store::DEFAULT_STORE_ID])
            ? $storeContentsData[Store::DEFAULT_STORE_ID]
            : '';

        $useDefaultValue = !isset($storeContentsData[$storeId]) && $defaultContents !== '';
        $storeContents = isset($storeContentsData[$storeId]) ? $storeContentsData[$storeId] : $defaultContents;

        $showUseDefaultValue = true;
        $contentReadonly = $readonly;
        if ($storeId == Store::DEFAULT_STORE_ID) {
            $showUseDefaultValue = false;
            $contentReadonly = $readonly || $saveAllContent === false;
        }

        $banner->setData('content_readonly', $contentReadonly);
        $banner->setData('readonly', $contentReadonly);
        $banner->setData('store_contents', $storeContents);
        $banner->setData('default_contents', $defaultContents);
        $banner->setData('use_default_value', $useDefaultValue);
        $banner->setData('show_use_default_value', $showUseDefaultValue);
        $banner->setData('store_id', $storeId);

        return $banner;
    }
}
