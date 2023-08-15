<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Includes;

use Magento\Staging\Model\ResourceModel\Update as UpdateResource;

/**
 * Class Hierarchy
 * @package Magento\Staging\Model\Update\Includes
 */
class Hierarchy
{
    /**
     * @var UpdateResource
     */
    protected $updateResource;

    /**
     * Hierarchy constructor.
     * @param UpdateResource $updateResource
     */
    public function __construct(
        UpdateResource $updateResource
    ) {
        $this->updateResource = $updateResource;
    }

    /**
     * Finds and sets the last company id associated with each update.
     *
     * @param array $includesData
     * @return array
     */
    public function changeIdToLast(array $includesData)
    {
        foreach ($includesData as &$include) {
            $include['created_in'] = $this->getTopId($include['created_in']);
        }
        return $includesData;
    }

    /**
     * @param int $id
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTopId($id)
    {
        $select = $this->updateResource->getConnection()
            ->select()
            ->from($this->updateResource->getMainTable(), ['id', 'moved_to']);
        $select->where('id = ?', $id);
        $result = $this->updateResource->getConnection()->fetchRow($select);
        if (isset($result['moved_to'])) {
            $topId = $this->getTopId($result['moved_to']);
        } else {
            $topId = $result['id'];
        }
        return $topId;
    }
}
