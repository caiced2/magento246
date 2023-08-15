<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Observer\Category\Controller\Save;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http as Request;

/**
 * Observer to unset custom design "Active From" and "Active To" field values in category save request
 */
class UnsetCustomDesignDates implements ObserverInterface
{
    private const FIELDS_TO_UNSET = [
        'custom_design_from',
        'custom_design_to',
    ];

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Request $request */
        $request = $observer->getData('request');
        $postValues = $request->getPostValue();
        foreach (self::FIELDS_TO_UNSET as $field) {
            unset($postValues[$field]);
        }
        $request->setPostValue($postValues);
    }
}
