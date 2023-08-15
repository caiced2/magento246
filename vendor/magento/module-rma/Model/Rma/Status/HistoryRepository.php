<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Rma\Status;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Class HistoryRepository
 * Repository class for \Magento\Rma\Model\Rma\Status\History
 */
class HistoryRepository
{
    /**
     * historyFactory
     *
     * @var \Magento\Rma\Model\Rma\Status\HistoryFactory
     */
    protected $historyFactory = null;

    /**
     * Collection Factory
     *
     * @var \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory
     */
    protected $historyCollectionFactory = null;

    /**
     * Magento\Rma\Model\Rma\Status\History[]
     *
     * @var array
     */
    protected $registry = [];

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * HistoryRepository constructor.
     * @param HistoryFactory $historyFactory
     * @param \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $historyCollectionFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        \Magento\Rma\Model\Rma\Status\HistoryFactory $historyFactory,
        \Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory $historyCollectionFactory,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->historyFactory = $historyFactory;
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * load entity
     *
     * @param int $id
     * @return \Magento\Rma\Model\Rma\Status\History
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id)
    {
        if (empty($id)) {
            throw new \Magento\Framework\Exception\InputException(__('ID cannot be an empty'));
        }
        if (!isset($this->registry[$id])) {
            $entity = $this->historyFactory->create()->load($id);
            if (!$entity->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __("The entity that was requested doesn't exist. Verify the entity and try again.")
                );
            }
            $this->registry[$id] = $entity;
        }
        return $this->registry[$id];
    }

    /**
     * Register entity
     *
     * @param \Magento\Rma\Model\Rma\Status\History $object
     * @return \Magento\Rma\Model\Rma\Status\HistoryRepository
     */
    public function register(\Magento\Rma\Model\Rma\Status\History $object)
    {
        if ($object->getId() && !isset($this->registry[$object->getId()])) {
            $object->load($object->getId());
            $this->registry[$object->getId()] = $object;
        }
        return $this;
    }

    /**
     * Find entities by criteria
     *
     * @param \Magento\Framework\Api\SearchCriteria $criteria
     * @return \Magento\Rma\Model\Rma\Status\History[]
     */
    public function find(\Magento\Framework\Api\SearchCriteria $criteria)
    {
        $collection = $this->historyCollectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        foreach ($collection as $object) {
            $this->register($object);
        }
        $objectIds = $collection->getAllIds();
        return array_intersect_key($this->registry, array_flip($objectIds));
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
                \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
            );
        }
        return $this->collectionProcessor;
    }
}
