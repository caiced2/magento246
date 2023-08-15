<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product;

/**
 * Update product gallery data
 */
class GalleryUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(
        Role $role
    ) {
        $this->role = $role;
    }

    /**
     * Set gallery data to product after data initialization
     *
     * @param Helper $subject
     * @param Product $result
     * @param Product $product
     * @param array $productData
     * @return Product
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitializeFromData(
        Helper $subject,
        Product $result,
        Product $product,
        array $productData
    ): Product {
        if ($result->isLockedAttribute('media_gallery') &&
            isset($productData['media_gallery']['images']) &&
            $this->role->hasStoreAccess($result->getStore()->getId())
        ) {
            $currentData = (array)$result->getData('media_gallery');
            foreach ($productData['media_gallery']['images'] as $key => $image) {
                if (isset($currentData['images'][$key])) {
                    $currentData['images'][$key]['label'] = $image['label'];
                    $currentData['images'][$key]['disabled'] = $image['disabled'];
                }
            }
            $result->unlockAttribute('media_gallery');
            $result->setData('media_gallery', $currentData);
            $result->lockAttribute('media_gallery');
        }

        return $result;
    }
}
