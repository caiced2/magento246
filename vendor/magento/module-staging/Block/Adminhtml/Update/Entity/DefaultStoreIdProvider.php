<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Block\Adminhtml\Update\Entity;

/**
 * Entity for default store ID provider
 * @codeCoverageIgnore
 */
class DefaultStoreIdProvider implements StoreIdProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getStoreId(): ?int
    {
        return null;
    }
}
