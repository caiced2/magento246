<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Collects and returns commerce edition and version.
 */
class CommerceEdition implements EventMetadataInterface
{
    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $commerceMetadata;

    /**
     * @param ProductMetadataInterface $commerceMetadata
     */
    public function __construct(ProductMetadataInterface $commerceMetadata)
    {
        $this->commerceMetadata = $commerceMetadata;
    }

    /**
     * Collects and returns commerce edition and version.
     *
     * @return array
     */
    public function get(): array
    {
        $commerceEdition = 'Adobe Commerce';

        switch ($this->commerceMetadata->getEdition()) {
            case 'Community':
                $commerceEdition = 'Open Source';
                break;
            case 'B2B':
                $commerceEdition = 'Adobe Commerce + B2B';
                break;
        }

        return [
            'commerceEdition' => $commerceEdition,
            'commerceVersion' => $this->commerceMetadata->getVersion()
        ];
    }
}
