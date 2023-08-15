<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Plugin\Helper;

use Magento\GoogleTagManager\Helper\Data as Helper;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

class Data
{
    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @param TagManagerConfig $tagManagerConfig
     */
    public function __construct(
        TagManagerConfig $tagManagerConfig
    ) {
        $this->tagManagerConfig = $tagManagerConfig;
    }

    /**
     * Tag Manager helper plugin
     *
     * @param Helper $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsTagManagerAvailable(Helper $subject, $result)
    {
        return $this->tagManagerConfig->isTagManagerAvailable() ?? $result;
    }
}
