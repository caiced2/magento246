<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Controller\Adminhtml\ContentType\DynamicBlock;

use Magento\Banner\Model\Banner;
use Magento\Framework\Controller\ResultFactory;

/**
 * Fetches meta information about dynamic blocks
 * @api
 */
class Metadata extends \Magento\Backend\App\AbstractAction
{
    /**
     * {@inheritdoc}
     */
    const ADMIN_RESOURCE = 'Magento_Banner::magento_banner';

    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\Collection
     */
    private $blockCollection;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    private $cartRuleCollection;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\Collection
     */
    private $catalogRuleCollection;

    /**
     * @var \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink
     */
    private $bannerSegmentLink;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
     */
    private $customerSegmentCollection;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Banner\Model\ResourceModel\Banner\Collection $blockCollection
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $cartRuleCollection
     * @param \Magento\CatalogRule\Model\ResourceModel\Rule\Collection $catalogRuleCollection
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection
     * @param \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Banner\Model\ResourceModel\Banner\Collection $blockCollection,
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $cartRuleCollection,
        \Magento\CatalogRule\Model\ResourceModel\Rule\Collection $catalogRuleCollection,
        \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection,
        \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->blockCollection = $blockCollection;
        $this->cartRuleCollection = $cartRuleCollection;
        $this->catalogRuleCollection = $catalogRuleCollection;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->customerSegmentCollection = $customerSegmentCollection;
        $this->logger = $logger;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            $blocks = $this->blockCollection
                ->addFieldToSelect(['name','is_enabled'])
                ->addFieldToFilter('banner_id', ['eq' => $params['block_id']])
                ->load();

            /* @var $block \Magento\Banner\Model\Banner */
            $block = $blocks->getFirstItem();
            $result = $block->toArray();

            $result['customer_segments'] = $this->getCustomerSegmentMetadata($block);
            $result['related_catalog_rules'] = $this->getCatalogRulesMetadata($block);
            $result['related_cart_rules'] = $this->getCartRulesMetadata($block);

            // Avoid having to reference "banner" all over the JS
            if (!empty($block->getId())) {
                $result['block_id'] = $block->getId();
            }
            // Remove unused fields to prevent accidental backwards-incompatible removal in the future
            unset($result['customer_segment_ids'], $result['banner_id'], $result['types']);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $result = [
                'error' => __('Something went wrong while getting the requested content.'),
                'errorcode' => $e->getCode()
            ];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Retrieves metadata for the assigned customer segments to the result array if there are any
     * @param Banner $block
     * @return array The metadata for the rules, if any
     */
    private function getCustomerSegmentMetadata(Banner $block): array
    {
        if (empty($block->getId())) {
            return [];
        }

        $segmentIds = $this->bannerSegmentLink->loadBannerSegments($block->getId());

        if (empty($segmentIds)) {
            return [];
        }

        $segments = $this->customerSegmentCollection
            ->addFieldToSelect(['name'])
            ->addFieldToFilter('segment_id', ['in' => $segmentIds])
            ->load();

        $metadata = [];

        foreach ($segments as $segment) {
            $metadata[] = $segment->getName();
        }

        return $metadata;
    }

    /**
     * Retrieves metadata for the related catalog rules to the result array if there are any
     * @param Banner $block
     * @return array The metadata for the rules, if any
     */
    private function getCatalogRulesMetadata(Banner $block): array
    {
        if (empty($block->getRelatedCatalogRule())) {
            return [];
        }

        $rules = $this->catalogRuleCollection
            ->addFieldToSelect(['name'])
            ->addFieldToFilter('rule_id', ['in' => $block->getRelatedCatalogRule()])
            ->load();

        $metadata = [];

        foreach ($rules as $rule) {
            $metadata[] = $rule->getName();
        }

        return $metadata;
    }

    /**
     * Retrieves metadata for the related cart rules to the result array if there are any
     * @param Banner $block
     * @return array The metadata for the rules, if any
     */
    private function getCartRulesMetadata(Banner $block): array
    {
        if (empty($block->getRelatedSalesRule())) {
            return [];
        }

        $rules = $this->cartRuleCollection
            ->addFieldToSelect(['name'])
            ->addFieldToFilter('rule_id', ['in' => $block->getRelatedSalesRule()])
            ->load();

        $metadata = [];

        foreach ($rules as $rule) {
            $metadata[] = $rule->getName();
        }

        return $metadata;
    }
}
