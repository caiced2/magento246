<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Quote;

/**
 * Class SubstractProductFromQuotes
 */
class SubstractProductFromQuotes
{
    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @param \Magento\Staging\Model\VersionManager $versionManager ,
     */
    public function __construct(\Magento\Staging\Model\VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Quote\Model\ResourceModel\Quote
     */
    public function aroundSubtractProductFromQuotes(
        \Magento\Quote\Model\ResourceModel\Quote $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            return $subject;
        }
        return $proceed($product);
    }
}
