<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Update\Grid;

/**
 * Interface ActionDataProviderInterface
 *
 * @api
 */
interface ActionDataProviderInterface
{
    /**
     * Get Button data for staging entity update grid
     *
     * @param array $item
     * @return array
     */
    public function getActionData($item);
}
