<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Model\ResourceModel;

use Exception;
use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @inheritDoc
 */
class Event extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('event_data', EventInterface::FIELD_ID);
    }

    /**
     * Deletes event_data table rows based on the specified where conditions.
     *
     * @param array $where
     * @return void
     * @throws Exception
     */
    public function deleteConditionally(array $where): void
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), $where);
    }
}
