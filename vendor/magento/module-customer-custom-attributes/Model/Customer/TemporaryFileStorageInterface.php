<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer;

/**
 * Customer temporary files storage interface
 */
interface TemporaryFileStorageInterface
{
    /**
     * Get temporary files from the storage
     *
     * @return array
     */
    public function get(): array;

    /**
     * Set temporary files in the storage
     *
     * @param array $value
     */
    public function set(array $value): void;

    /**
     * Clean temporary files in the storage
     */
    public function clean(): void;
}
