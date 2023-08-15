<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity;

use Magento\Framework\Model\AbstractModel;

/**
 * Interface \Magento\Staging\Model\Entity\HydratorInterface
 *
 * @api
 */
interface HydratorInterface
{
    /**
     * Hydrate model with data
     *
     * @param array $data
     * @return AbstractModel
     */
    public function hydrate(array $data);
}
