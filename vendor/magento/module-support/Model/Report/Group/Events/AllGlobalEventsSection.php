<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Events;

use Magento\Framework\App\Area;

/**
 * All global events section
 */
class AllGlobalEventsSection extends AbstractEventsSection
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAreaCode()
    {
        return Area::AREA_GLOBAL;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return (string)__('All Global events');
    }
}
