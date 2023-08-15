<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Model\Condition;

use Magento\Framework\DataObject;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface
 *
 * @api
 */
interface FilterTextGeneratorInterface
{
    /**
     * @param DataObject $input
     * @return string[]
     */
    public function generateFilterText(DataObject $input);
}
