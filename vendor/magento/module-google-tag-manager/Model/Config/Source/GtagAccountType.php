<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Model\Config\Source;

use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * Provide account types
 *
 * @api
 */
class GtagAccountType
{
    /**
     * Return account type
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => TagManagerConfig::TYPE_ANALYTICS4,
                'label' => __('Google Analytics4')
            ],[
                'value' => TagManagerConfig::TYPE_TAG_MANAGER,
                'label' => __('Google Tag Manager')
            ],
        ];
    }
}
