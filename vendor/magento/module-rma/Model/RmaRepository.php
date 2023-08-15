<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\Data\RmaSearchResultInterfaceFactory;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma\CanReturn;
use Magento\Rma\Model\Spi\RmaResourceInterface;

/**
 * Class for saving, updating and deleting Return Merchandise Approvals.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RmaRepository implements RmaRepositoryInterface
{
    /**
     * rmaFactory
     *
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $rmaFactory;

    /**
     * Collection Factory
     *
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $rmaCollectionFactory;

    /**
     * Magento\Rma\Model\Rma[]
     *
     * @var array
     */
    protected $registry = [];

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var RmaResourceInterface|null
     */
    private $rmaResource;

    /**
     * @var RmaSearchResultInterfaceFactory|null
     */
    private $searchResultFactory;

    /**
     * @var CanReturn
     */
    private $returnValidator;

    /**
     * @param RmaFactory $rmaFactory
     * @param ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param RmaResourceInterface|null $rmaResource
     * @param RmaSearchResultInterfaceFactory|null $searchResultFactory
     * @param CanReturn|null $returnValidator
     */
    public function __construct(
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        CollectionProcessorInterface $collectionProcessor = null,
        RmaResourceInterface $rmaResource = null,
        RmaSearchResultInterfaceFactory $searchResultFactory = null,
        CanReturn $returnValidator = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->rmaFactory = $rmaFactory;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->collectionProcessor = $collectionProcessor ?: $objectManager->get(CollectionProcessorInterface::class);
        $this->rmaResource = $rmaResource ?: $objectManager->get(RmaResourceInterface::class);
        $this->searchResultFactory = $searchResultFactory ?:
            $objectManager->get(RmaSearchResultInterfaceFactory::class);
        $this->returnValidator = $returnValidator ?: $objectManager->get(CanReturn::class);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        /** @var Rma $entity */
        $entity = $this->rmaFactory->create();
        $this->rmaResource->load($entity, $id);
        return $entity;
    }

    /**
     * Register entity
     *
     * @param Rma $object
     * @return \Magento\Rma\Model\RmaRepository
     * @deprecated 101.0.0
     * @see \Magento\Rma\Model\RmaRepository::get
     */
    public function register(\Magento\Rma\Model\Rma $object)
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
     * @return \Magento\Rma\Model\Rma[]
     * @deprecated 101.0.0
     * @see \Magento\Rma\Model\RmaRepository::getList
     */
    public function find(\Magento\Framework\Api\SearchCriteria $criteria)
    {
        $collection = $this->rmaCollectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);
        foreach ($collection as $object) {
            $this->register($object);
        }
        $objectIds = $collection->getAllIds();
        return array_intersect_key($this->registry, array_flip($objectIds));
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->rmaCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $collection->setSearchCriteria($searchCriteria);
        return $collection;
    }

    /**
     * @inheritdoc
     *
     * @throws CouldNotSaveException
     */
    public function save(RmaInterface $rmaDataObject)
    {
        if (!$this->returnValidator->validate($rmaDataObject)) {
            throw new CouldNotSaveException(__('Could not save the RMA entity.'));
        }

        try {
            $this->rmaResource->save($rmaDataObject);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the RMA entity.'), $e);
        }
        return $this->get($rmaDataObject->getEntityId());
    }

    /**
     * @inheritdoc
     *
     * @throws CouldNotDeleteException
     */
    public function delete(RmaInterface $rmaDataObject)
    {
        try {
            $this->rmaResource->delete($rmaDataObject);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the RMA entity.'), $e);
        }
        return true;
    }
}
