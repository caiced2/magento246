<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Api;

/**
 * Interface GiftCardAccountRepositoryInterface
 * @api
 * @since 100.0.2
 */
interface GiftCardAccountRepositoryInterface
{
    /**
     * Return data object for specified GiftCard Account id
     *
     * @param int $id
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface
     */
    public function get($id);

    /**
     * Return list of GiftCard Account data objects based on search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardAccountSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save GiftCard Account
     *
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftDataObject
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftDataObject);

    /**
     * Delete GiftCard Account
     *
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftDataObject
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function delete(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftDataObject);
}
