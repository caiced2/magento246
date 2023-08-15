<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Api\Data;

/**
 * Page Node interface.
 * @api
 * @since 100.0.2
 */
interface HierarchyNodeInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const NODE_ID        = 'node_id';
    const PARENT_NODE_ID = 'parent_node_id';
    const PAGE_ID        = 'page_id';
    const IDENTIFIER     = 'identifier';
    const LABEL          = 'label';
    const LEVEL          = 'level';
    const SORT_ORDER     = 'sort_order';
    const REQUEST_URL    = 'request_url';
    const XPATH          = 'xpath';
    const SCOPE          = 'scope';
    const SCOPE_ID       = 'scope_id';
    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get parent ID
     *
     * @return int|null
     */
    public function getParentId();

    /**
     * Get page ID
     *
     * @return int
     */
    public function getPageId();

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Get label
     *
     * @return string|null
     */
    public function getLabel();

    /**
     * Get level
     *
     * @return int|null
     */
    public function getLevel();

    /**
     * Get sort order
     *
     * @return int|null
     */
    public function getSortOrder();

    /**
     * Get request url
     *
     * @return string|null
     */
    public function getRequestUrl();

    /**
     * Get xpath
     *
     * @return string|null
     */
    public function getXpath();

    /**
     * Get scope
     *
     * @return string|null
     */
    public function getScope();

    /**
     * Get scope ID
     *
     * @return int|null
     */
    public function getScopeId();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setId($id);

    /**
     * Set parent ID
     *
     * @param int $parentId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setParentId($parentId);

    /**
     * Set page ID
     *
     * @param int $pageId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setPageId($pageId);

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setIdentifier($identifier);

    /**
     * Set label
     *
     * @param string $label
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setLabel($label);

    /**
     * Set level
     *
     * @param int $level
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setLevel($level);

    /**
     * Set sort order
     *
     * @param string $sortOrder
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * Set request url
     *
     * @param string $requestUrl
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setRequestUrl($requestUrl);

    /**
     * Set xpath
     *
     * @param string $xpath
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setXpath($xpath);

    /**
     * Set scope ID
     *
     * @param int $scopeId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setScopeId($scopeId);

    /**
     * Set nodes scope
     *
     * @param string $scope
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setScope($scope);
}
