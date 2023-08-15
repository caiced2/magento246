<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Config;

class Data extends \Magento\Framework\Config\Data\Scoped
{
    /**
     * Supported reports configuration data container
     *
     * @var string[]
     */
    protected $_scopePriorityScheme = ['global'];
}
