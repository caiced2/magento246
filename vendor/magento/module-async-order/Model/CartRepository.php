<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Repository for quote entity where getActive method is overloaded because async quote is inactive after placing order.
 */
class CartRepository implements CartRepositoryInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * Constructor
     *
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritdoc
     */
    public function get($cartId, array $sharedStoreIds = [])
    {
        return $this->quoteRepository->get($cartId, $sharedStoreIds);
    }

    /**
     * @inheritdoc
     */
    public function getForCustomer($customerId, array $sharedStoreIds = [])
    {
        return $this->quoteRepository->getForCustomer($customerId, $sharedStoreIds);
    }

    /**
     * @inheritdoc
     */
    public function getActive($cartId, array $sharedStoreIds = [])
    {
        return $this->get($cartId, $sharedStoreIds);
    }

    /**
     * @inheritdoc
     */
    public function getActiveForCustomer($customerId, array $sharedStoreIds = [])
    {
        return $this->quoteRepository->getActiveForCustomer($customerId, $sharedStoreIds);
    }

    /**
     * @inheritdoc
     */
    public function save(CartInterface $quote)
    {
        $this->quoteRepository->save($quote);
    }

    /**
     * @inheritdoc
     */
    public function delete(CartInterface $quote)
    {
        $this->quoteRepository->delete($quote);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->quoteRepository->getList($searchCriteria);
    }
}
