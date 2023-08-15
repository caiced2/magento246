<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules;

/**
 * Interface RuleInterface
 * @package Magento\VisualMerchandiser\Model\Rules
 * @api
 * @since 100.0.2
 */
interface RuleInterface
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyToCollection($collection);

    /**
     * @return \Magento\VisualMerchandiser\Model\Rules\RuleInterface
     */
    public function get();

    /**
     * @return array
     */
    public function getNotices();

    /**
     * @return bool
     */
    public function hasNotices();
}
