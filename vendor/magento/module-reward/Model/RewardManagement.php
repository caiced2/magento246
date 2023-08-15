<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model;

class RewardManagement implements \Magento\Reward\Api\RewardManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $rewardData;

    /**
     * @var \Magento\Reward\Model\PaymentDataImporter
     */
    protected $importer;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param PaymentDataImporter $importer
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Reward\Model\PaymentDataImporter $importer
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->rewardData = $rewardData;
        $this->importer = $importer;
    }

    /**
     * {@inheritdoc}
     */
    public function set($cartId)
    {
        if ($this->rewardData->isEnabledOnFront()) {
            /* @var $quote \Magento\Quote\Model\Quote */
            $quote = $this->quoteRepository->getActive($cartId);
            $this->importer->import($quote, $quote->getPayment(), true);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);
            return true;
        }
        return false;
    }
}
