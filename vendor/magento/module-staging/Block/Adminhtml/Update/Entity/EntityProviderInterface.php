<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Block\Adminhtml\Update\Entity;

/**
 * Interface EntityProviderInterface
 *
 * @api
 */
interface EntityProviderInterface
{
    /**
     * Return Entity ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Return Entity Url in version
     *
     * @param int $updateId
     * @return null|string
     */
    public function getUrl($updateId);
}
