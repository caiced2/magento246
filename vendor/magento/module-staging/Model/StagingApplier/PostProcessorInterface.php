<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\StagingApplier;

/**
 * Staging applier post processor interface
 */
interface PostProcessorInterface
{
    /**
     * Runs postprocessors for updated entities
     *
     * @param int $oldVersionId
     * @param int $currentVersionId
     * @param array $entityIds
     * @param string $entityType
     */
    public function execute(
        int $oldVersionId,
        int $currentVersionId,
        array $entityIds,
        string $entityType
    ): void;
}
