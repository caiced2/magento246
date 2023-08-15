<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel\Rma\Status\History;

use Magento\Sales\Model\ResourceModel\Collection\AbstractCollection;
use Magento\Rma\Api\Data\CommentSearchResultInterface;

/**
 * RMA entity resource model
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Collection extends AbstractCollection implements CommentSearchResultInterface
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\Rma\Model\Rma\Status\History::class,
            \Magento\Rma\Model\ResourceModel\Rma\Status\History::class
        );
    }
}
