<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Operation\Update;

/**
 * Interface \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface
 *
 * @api
 */
interface UpdateProcessorInterface
{
    /**
     * Process update
     *
     * @param object $entity
     * @param int $versionId
     * @param int $rollbackId
     * @return object
     */
    public function process($entity, $versionId, $rollbackId = null);
}
