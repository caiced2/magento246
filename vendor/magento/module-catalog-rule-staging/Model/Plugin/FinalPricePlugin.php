<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Model\Plugin;

use Magento\Staging\Model\VersionManager;
use Magento\CatalogRule\Observer\ProcessFrontFinalPriceObserver;

/**
 * Class for changing final price in preview.
 */
class FinalPricePlugin
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param VersionManager $versionManager
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * Change final price in preview mode.
     *
     * @param ProcessFrontFinalPriceObserver $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return ProcessFrontFinalPriceObserver
     */
    public function aroundExecute(
        ProcessFrontFinalPriceObserver $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            $product = $observer->getEvent()->getProduct();
            $product->setFinalPrice($product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue());
            return $subject;
        } else {
            return $proceed($observer);
        }
    }
}
