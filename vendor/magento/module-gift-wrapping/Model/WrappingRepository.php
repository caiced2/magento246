<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Magento\GiftWrapping\Api\Data\WrappingSearchResultsInterfaceFactory;
use Magento\Framework\Exception\StateException;
use Magento\Store\Model\Store;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WrappingRepository implements WrappingRepositoryInterface
{
    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $wrappingFactory;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var WrappingSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping
     */
    protected $resourceModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param WrappingFactory $wrappingFactory
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param WrappingSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        WrappingSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->wrappingFactory = $wrappingFactory;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceModel = $resource;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Load wrapping model for specified store
     *
     * @param int $id
     * @param int $storeId
     * @return \Magento\GiftWrapping\Model\Wrapping
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id, $storeId = null)
    {
        /** @var \Magento\GiftWrapping\Model\Wrapping $wrapping */
        $wrapping = $this->wrappingFactory->create();
        $wrapping->setStoreId($storeId);
        $this->resourceModel->load($wrapping, $id);
        if (!$wrapping->getId()) {
            throw new NoSuchEntityException(
                __('Gift wrapping with the %1 ID wasn\'t found. Verify the ID and try again.', $id)
            );
        }
        $wrapping->setWebsiteIds($wrapping->getWebsiteIds());
        $wrapping->setImageName($wrapping->getImage());
        $wrapping->setImageUrl($wrapping->getImageUrl());
        return $wrapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        /** @var  \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection $collection */
        $collection = $this->wrappingCollectionFactory->create();
        $collection->addWebsitesToResult();
        $this->collectionProcessor->process($searchCriteria, $collection);
        return $this->searchResultsFactory->create()
            ->setItems($collection->getItems())
            ->setTotalCount($collection->getSize())
            ->setSearchCriteria($searchCriteria);
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Magento\GiftWrapping\Api\Data\WrappingInterface $data, $storeId = null)
    {
        $id = $data->getWrappingId();
        $currencyCode = $data->getBaseCurrencyCode();
        $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
        if (isset($currencyCode) && ($currencyCode != $baseCurrencyCode)) {
            throw new StateException(
                __(
                    'A valid currency code wasn\'t entered. Enter a valid %1 currency code and try again.',
                    $baseCurrencyCode
                )
            );
        }
        if ($id) {
            $data = $this->get($id)->addData($data->getData());
        }
        $imageContent = base64_decode($data->getImageBase64Content(), true);
        if ($storeId === null) {
            $storeId = Store::DEFAULT_STORE_ID;
        }
        $data->setStoreId($storeId);
        $data->attachBinaryImage($data->getImageName(), $imageContent);
        $this->resourceModel->save($data);
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Magento\GiftWrapping\Api\Data\WrappingInterface $data)
    {
        try {
            $this->resourceModel->delete($data);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('The "%1" gift wrapping couldn\'t be removed.', $data->getWrappingId())
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        $model = $this->get($id);
        $this->delete($model);
        return true;
    }

    /**
     * Retrieve collection processor
     *
     * @deprecated 101.0.0
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\GiftWrapping\Api\SearchCriteria\WrappingCollectionProcessor'
            );
        }
        return $this->collectionProcessor;
    }
}
