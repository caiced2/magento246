<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterfaceFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node as ResourceNode;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Class NodeRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NodeRepository implements HierarchyNodeRepositoryInterface
{
    /**
     * @var ResourceNode
     */
    protected $resource;

    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var CollectionFactory
     */
    protected $nodeCollectionFactory;

    /**
     * @var HierarchyNodeSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var HierarchyNodeInterfaceFactory
     */
    protected $dataNodeFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @param ResourceNode $resource
     * @param NodeFactory $nodeFactory
     * @param HierarchyNodeInterfaceFactory $dataNodeFactory
     * @param CollectionFactory $nodeCollectionFactory
     * @param HierarchyNodeSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CollectionProcessorInterface | null $collectionProcessor
     */
    public function __construct(
        ResourceNode $resource,
        NodeFactory $nodeFactory,
        HierarchyNodeInterfaceFactory $dataNodeFactory,
        CollectionFactory $nodeCollectionFactory,
        HierarchyNodeSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->resource = $resource;
        $this->nodeFactory = $nodeFactory;
        $this->nodeCollectionFactory = $nodeCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataNodeFactory = $dataNodeFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Save Node data
     *
     * @param \Magento\VersionsCms\Api\Data\HierarchyNodeInterface $node
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     * @throws CouldNotSaveException
     */
    public function save(\Magento\VersionsCms\Api\Data\HierarchyNodeInterface $node)
    {
        try {
            $this->resource->save($node);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $node;
    }

    /**
     * Load Node data by given Node Identity
     *
     * @param string $nodeId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($nodeId)
    {
        $node = $this->nodeFactory->create();
        $this->resource->load($node, $nodeId);
        if (!$node->getId()) {
            throw new NoSuchEntityException(__('The CMS node with the "%1" ID doesn\'t exist.', $nodeId));
        }
        return $node;
    }

    /**
     * Load Node data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->nodeCollectionFactory->create();
        $collection->joinCmsPage()
            ->joinMetaData()
            ->addCmsPageInStoresColumn()
            ->addLastChildSortOrderColumn();
        $this->collectionProcessor->process($criteria, $collection);
        $searchResults->setTotalCount($collection->getSize());
        $nodes = [];
        /** @var Node $nodeModel */
        foreach ($collection as $nodeModel) {
            $nodeData = $this->nodeFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $nodeData,
                $nodeModel->getData(),
                \Magento\VersionsCms\Api\Data\HierarchyNodeInterface::class
            );
            $nodes[] = $this->dataObjectProcessor->buildOutputDataArray(
                $nodeData,
                \Magento\VersionsCms\Api\Data\HierarchyNodeInterface::class
            );
        }
        $searchResults->setItems($nodes);
        return $searchResults;
    }

    /**
     * Delete Node
     *
     * @param \Magento\VersionsCms\Api\Data\HierarchyNodeInterface $node
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Magento\VersionsCms\Api\Data\HierarchyNodeInterface $node)
    {
        try {
            $this->resource->delete($node);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Node by given Node Identity
     *
     * @param string $nodeId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($nodeId)
    {
        return $this->delete($this->getById($nodeId));
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
                'Magento\VersionsCms\Api\SearchCriteria\HierarchyNodeCollectionProcessor'
            );
        }
        return $this->collectionProcessor;
    }
}
