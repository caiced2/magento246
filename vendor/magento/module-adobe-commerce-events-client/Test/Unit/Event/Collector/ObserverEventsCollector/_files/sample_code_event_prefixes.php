<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Module;

use Magento\Framework\Model\AbstractModel;

class SampleClass extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'sample_class';
}
