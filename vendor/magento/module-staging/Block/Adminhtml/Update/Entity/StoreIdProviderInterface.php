<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Block\Adminhtml\Update\Entity;

/**
 * Interface StoreIdProviderInterface
 *
 * @api
 */
interface StoreIdProviderInterface
{
    /**
     * Return Entity Store Id
     *
     * @return int|null
     */
    public function getStoreId(): ?int;
}
