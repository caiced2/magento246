<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\ResourceModel\Plugin\Rule;

use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorFactory;
use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\Quote\Model\Quote\Address;
use \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\FilterFactory as FilterResourceFactory;

class Collection
{
    /**
     * @var FilterResourceFactory
     */
    protected $filterResourceFactory;

    /**
     * @var FilterTextGeneratorFactory
     */
    protected $filterTextGeneratorFactory;

    /**
     * @param FilterResourceFactory $filterResourceFactory
     * @param FilterTextGeneratorFactory $filterTextGeneratorFactory
     */
    public function __construct(
        FilterResourceFactory $filterResourceFactory,
        FilterTextGeneratorFactory $filterTextGeneratorFactory
    ) {
        $this->filterResourceFactory = $filterResourceFactory;
        $this->filterTextGeneratorFactory = $filterTextGeneratorFactory;
    }

    /**
     * Around plugin needed to capture original parameters for further processing
     *
     * @param RuleCollection $subject
     * @param \Closure $proceed
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @param Address $address
     * @return RuleCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSetValidationFilter(
        RuleCollection $subject,
        \Closure $proceed,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        Address $address = null
    ) {
        if (($couponCode !== null && strlen($couponCode))
            || $address === null
            || $address->getData('skip_validation_filter') === true) {
            // do not add any additional filtering; just allow everything to proceed naturally
            return $proceed($websiteId, $customerGroupId, $couponCode, $now);
        }

        /** @var RuleCollection $result */
        $result = $proceed($websiteId, $customerGroupId, $couponCode, $now);
        $filteredRuleIds = $this->getFilteredRuleIds($address);
        $connection = $result->getConnection();
        $result->getSelect()->where(
            $connection->quoteInto(
                'main_table.rule_id IN (?) ',
                $filteredRuleIds
            )
        );
        return $result;
    }

    /**
     * Returns Rule IDs after filtering.
     *
     * @param Address $address
     * @return array
     */
    protected function getFilteredRuleIds(Address $address)
    {
        /** @var \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel */
        $filterResourceModel = $this->filterResourceFactory->create();
        $filterTextGenerators = $filterResourceModel->getFilterTextGenerators();
        $filterCriteria = [['true']];
        foreach ($filterTextGenerators as $filterGeneratorData) {
            $filterTextGeneratorClass = $filterGeneratorData[Filter::KEY_FILTER_TEXT_GENERATOR_CLASS];
            $filterTextGeneratorArguments = $filterGeneratorData[Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS];
            $filterTextGeneratorArguments = json_decode($filterTextGeneratorArguments, true);

            $filterTextGenerator = $this->filterTextGeneratorFactory->create(
                $filterTextGeneratorClass,
                ['data' => $filterTextGeneratorArguments]
            );
            $filterCriteria[] = $filterTextGenerator->generateFilterText($address);
        }
        $filterCriteria = array_merge([], ...$filterCriteria);
        $filterCriteria = array_unique($filterCriteria);

        return $filterResourceModel->filterRules($filterCriteria);
    }
}
