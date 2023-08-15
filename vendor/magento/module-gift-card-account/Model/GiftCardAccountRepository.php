<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterfaceFactory;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountSearchResultInterfaceFactory;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountResourceInterface;
use Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\CollectionFactory;

/**
 * @inheritDoc
 */
class GiftCardAccountRepository implements GiftCardAccountRepositoryInterface
{
    /**
     * @var GiftCardAccountResourceInterface
     */
    private $giftCardAccountResource;

    /**
     * @var GiftCardAccountInterfaceFactory
     */
    private $giftCardAccountFactory;

    /**
     * @var GiftCardAccountSearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param GiftCardAccountResourceInterface $giftCardAccountResource
     * @param GiftCardAccountInterfaceFactory $giftCardAccountFactory
     * @param CollectionFactory $collectionFactory
     * @param GiftCardAccountSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        GiftCardAccountResourceInterface $giftCardAccountResource,
        GiftCardAccountInterfaceFactory $giftCardAccountFactory,
        CollectionFactory $collectionFactory,
        GiftCardAccountSearchResultInterfaceFactory $searchResultFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->giftCardAccountResource = $giftCardAccountResource;
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $entity = $this->giftCardAccountFactory->create();
        $this->giftCardAccountResource->load($entity, $id);
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function save(GiftCardAccountInterface $giftDataObject)
    {
        try {
            $this->giftCardAccountResource->save($giftDataObject);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the gift card account.'), $e);
        }
        return $giftDataObject;
    }

    /**
     * @inheritdoc
     */
    public function delete(GiftCardAccountInterface $giftDataObject)
    {
        try {
            $this->giftCardAccountResource->delete($giftDataObject);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the gift card account.'));
        }
        return true;
    }
}
