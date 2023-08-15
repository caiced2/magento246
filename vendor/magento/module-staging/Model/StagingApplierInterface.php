<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model;

/**
 * Interface StagingApplierInterface
 *
 * @api
 */
interface StagingApplierInterface
{
    /**
     * Runs applying version to entity
     *
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds);
}
