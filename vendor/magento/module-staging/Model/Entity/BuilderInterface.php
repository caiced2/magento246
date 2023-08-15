<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity;

/**
 * Interface \Magento\Staging\Model\Entity\BuilderInterface
 *
 * @api
 */
interface BuilderInterface
{
    /**
     * Build entity by prototype
     *
     * @param object $prototype
     * @return object
     */
    public function build($prototype);
}
