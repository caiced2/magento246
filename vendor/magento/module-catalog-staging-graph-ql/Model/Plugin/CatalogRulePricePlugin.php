<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Plugin;

use Magento\CatalogRuleStaging\Pricing\Price\CatalogRulePrice;
use Magento\CatalogStagingGraphQl\Model\Products\StagedProductCollector;
use Magento\Staging\Model\VersionManager;

/**
 * Plugin to keep track of products that are dynamically affected by catalog rules during preview
 */
class CatalogRulePricePlugin
{
    /**
     * @var StagedProductCollector
     */
    private $stagedProductCollector;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param StagedProductCollector $stagedProductCollector
     * @param VersionManager $versionManager
     */
    public function __construct(
        StagedProductCollector $stagedProductCollector,
        VersionManager $versionManager
    ) {
        $this->stagedProductCollector = $stagedProductCollector;
        $this->versionManager = $versionManager;
    }

    /**
     * If price is changed during pluginized method call, flag product as "Staged"
     *
     * @param CatalogRulePrice $subject
     * @param \Closure $proceed
     * @return float|boolean
     */
    public function aroundGetValue(CatalogRulePrice $subject, \Closure $proceed)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return $proceed();
        }
        $initialFinalPriceValue = $subject->getProduct()->getFinalPrice();
        $actualFinalPriceValue = $proceed();
        //If price was adjusted, flag as staged
        if ($actualFinalPriceValue < $initialFinalPriceValue) {
            $this->stagedProductCollector->addProductSku($subject->getProduct()->getSku());
            //For complex products we also need to flag the parents as staged
            if ($subject->getProduct()->getParentId()) {
                $this->stagedProductCollector->addProductId((int)$subject->getProduct()->getParentId());
            }
            if ($subject->getProduct()->getParentProductId()) {
                $this->stagedProductCollector->addProductId((int)$subject->getProduct()->getParentProductId());
            }
            if ($subject->getProduct()->getData('_linked_to_product_id')) {
                $this->stagedProductCollector->addProductId(
                    (int)$subject->getProduct()->getData('_linked_to_product_id')
                );
            }
        }
        return $actualFinalPriceValue;
    }
}
