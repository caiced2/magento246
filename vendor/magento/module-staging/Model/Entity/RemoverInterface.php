<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity;

/**
 * Interface RemoverInterface
 *
 * @api
 */
interface RemoverInterface
{
    /**
     * @param object $entity
     * @param string $versionId
     * @return boolean
     */
    public function deleteEntity($entity, $versionId);
}
