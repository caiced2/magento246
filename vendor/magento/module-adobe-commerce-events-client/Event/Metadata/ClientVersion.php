<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

use Magento\Framework\Module\PackageInfo;

/**
 * Collects and returns version of the Adobe Commerce events client module.
 */
class ClientVersion implements EventMetadataInterface
{
    /**
     * @var PackageInfo
     */
    private PackageInfo $packageInfo;

    /**
     * @param PackageInfo $packageInfo
     */
    public function __construct(PackageInfo $packageInfo)
    {
        $this->packageInfo = $packageInfo;
    }

    /**
     * Collects and returns version of the Adobe Commerce events client module.
     *
     * @return array
     */
    public function get(): array
    {
        return [
            'eventsClientVersion' => $this->packageInfo->getVersion('Magento_AdobeCommerceEventsClient')
        ];
    }
}
