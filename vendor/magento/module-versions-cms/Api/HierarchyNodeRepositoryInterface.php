<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Api;

/**
 * CMS Hierarchy Node CRUD interface.
 * @api
 * @since 100.0.2
 */
interface HierarchyNodeRepositoryInterface
{
    /**
     * Save hierarchy node.
     *
     * @param \Magento\VersionsCms\Api\Data\HierarchyNodeInterface $hierarchyNode
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\HierarchyNodeInterface $hierarchyNode);

    /**
     * Retrieve hierarchy node.
     *
     * @param int $nodeId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($nodeId);

    /**
     * Retrieve hierarchy nodes matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete hierarchy node.
     *
     * @param \Magento\VersionsCms\Api\Data\HierarchyNodeInterface $hierarchyNode
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\HierarchyNodeInterface $hierarchyNode);

    /**
     * Delete hierarchy node by ID.
     *
     * @param int $nodeId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($nodeId);
}
