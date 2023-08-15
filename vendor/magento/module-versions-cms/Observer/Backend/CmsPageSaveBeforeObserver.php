<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use InvalidArgumentException;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;

/**
 * Observer for cms pages save operation
 */
class CmsPageSaveBeforeObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @param Data $jsonHelper
     */
    public function __construct(
        Data $jsonHelper
    ) {
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Prepare cms page object before it will be saved
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var Page $page */
        $page = $observer->getEvent()->getObject();
        $nodesData = $this->getNodesOrder($page->getNodesData());
        if (!$page->getId() && empty($nodesData['appendToNodes'])) {
            // Newly created page should be auto assigned to website root
            $page->setWebsiteRoot(true);
        }
        $page->setNodesSortOrder($nodesData['sortOrder']);
        $page->setAppendToNodes($nodesData['appendToNodes']);
        return $this;
    }

    /**
     * Check nodes data and return new sort order for nodes
     *
     * @param string $nodesData
     * @return array
     */
    protected function getNodesOrder($nodesData)
    {
        $appendToNodes = ($nodesData === null) ? null : [];
        $sortOrder = [];

        if ($nodesData !== null) {
            try {
                $nodesData = $this->jsonHelper->jsonDecode($nodesData);
            } catch (InvalidArgumentException $e) {
                $nodesData = null;
            }
            if (!empty($nodesData)) {
                $dataArray = $this->populateDataArray($nodesData);
                $appendToNodes = $dataArray['appendToNodes'];
                $sortOrder = $dataArray['sortOrder'];
            }
        }

        return [
            'appendToNodes' => $appendToNodes,
            'sortOrder' => $sortOrder
        ];
    }

    /**
     * Populate data array with sortOrder and appendToNodes data
     *
     * @param array $nodesData
     * @return array
     */
    private function populateDataArray(array $nodesData): array
    {
        $appendToNodes = [];
        $sortOrder = [];

        foreach ($nodesData as $row) {
            if (isset($row['page_exists']) && $row['page_exists']) {
                $appendToNodes[$row['node_id']] = 0;
            }

            if (isset($appendToNodes[$row['parent_node_id']])) {
                if (strpos($row['node_id'], '_') !== false) {
                    $appendToNodes[$row['parent_node_id']] = $row['sort_order'];
                } else {
                    $sortOrder[$row['node_id']] = $row['sort_order'];
                }
            }
        }

        $result = [
            'appendToNodes' => $appendToNodes,
            'sortOrder' => $sortOrder
        ];

        return $result;
    }
}
