<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity;

/**
 * Interface PersisterInterface
 *
 * @api
 */
interface PersisterInterface
{
    /**
     * @param object $entity
     * @param string $versionId
     * @return bool mixed
     */
    public function saveEntity($entity, $versionId);
}
