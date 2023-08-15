<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Coupon;

use Magento\Framework\Event\ObserverInterface;

class ExpirationDateResolver implements ObserverInterface
{
    /**
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface
     */
    private $couponRepository;

    /**
     * Filter Builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;

    /**
     * ExpirationDateResolver constructor.
     * @param \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->couponRepository = $couponRepository;
        $this->filterBuilder = $filterBuilder;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
    }

    /**
     * Updating coupon expiration date according to applied sales rule update
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     *
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $ids = $observer->getData('entity_ids');

        //load coupon collection that matched ids
        $this->criteriaBuilder->addFilters(
            [$this->filterBuilder->setField('rule_id')->setValue($ids)->setConditionType('in')->create()]
        );
        $searchCriteria = $this->criteriaBuilder->create();
        $coupons = $this->couponRepository->getList($searchCriteria);

        /** @var \Magento\SalesRule\Api\Data\CouponInterface $coupon */
        foreach ($coupons->getItems() as $coupon) {
            try {
                $ruleId = $coupon->getRuleId();
                /** @var  \Magento\SalesRule\Api\Data\RuleInterface $rule */
                $rule = $this->ruleRepository->getById($ruleId);

                $coupon->setExpirationDate($rule->getToDate());
                $this->couponRepository->save($coupon);
            } catch (\Exception $e) {
                //do nothing continue with updating other coupons
                $message = __(
                    'An error occurred during processing; coupon with id %1 expiration date'
                    . ' wasn\'t updated. The error message was: %2',
                    $coupon->getCouponId(),
                    $e->getMessage()
                );
                $this->logger->error($message);
            }
        }
    }
}
