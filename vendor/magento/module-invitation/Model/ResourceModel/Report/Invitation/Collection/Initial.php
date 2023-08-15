<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Report Reviews collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\ResourceModel\Report\Invitation\Collection;

class Initial extends \Magento\Reports\Model\ResourceModel\Report\Collection
{
    /**
     *  Report sub-collection class name
     *
     * @var string
     */
    protected $_reportCollection = \Magento\Invitation\Model\ResourceModel\Report\Invitation\Collection::class;
}
