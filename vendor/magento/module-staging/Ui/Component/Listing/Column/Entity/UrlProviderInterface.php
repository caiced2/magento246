<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Ui\Component\Listing\Column\Entity;

/**
 * Interface \Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface
 *
 * @api
 */
interface UrlProviderInterface
{
    /**
     * Get URL for data provider item
     *
     * @param array $item
     * @return string
     */
    public function getUrl(array $item);
}
