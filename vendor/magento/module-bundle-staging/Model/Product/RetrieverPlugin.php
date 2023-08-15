<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleStaging\Model\Product;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Framework\DataObject;

/**
 * Update Bundle product with proper values for can_save_bundle_selections property
 */
class RetrieverPlugin
{
    /**
     * Update result with proper value
     *
     * @param RetrieverInterface $subject
     * @param DataObject $result
     * @return DataObject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetEntity(
        RetrieverInterface $subject,
        $result
    ) {
        if ($result->getTypeId() === BundleType::TYPE_CODE) {
            if ((int)$result->getData("has_options") === 1 && (int)$result->getData("required_options") === 1) {
                $result->setCanSaveBundleSelections(true);
                $result->setTypeHasOptions(true);
                $result->setTypeHasRequiredOptions(true);
            }
        }
        return $result;
    }
}
