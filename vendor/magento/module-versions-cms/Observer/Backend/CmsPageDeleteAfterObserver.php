<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CmsPageDeleteAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Increment
     */
    protected $cmsIncrement;

    /**
     * @param \Magento\VersionsCms\Model\ResourceModel\Increment $cmsIncrement
     */
    public function __construct(
        \Magento\VersionsCms\Model\ResourceModel\Increment $cmsIncrement
    ) {
        $this->cmsIncrement = $cmsIncrement;
    }

    /**
     * Remove unneeded data from increment table for removed page.
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $observer->getEvent()->getObject();

        $this->cmsIncrement->cleanIncrementRecord(
            \Magento\VersionsCms\Model\Increment::TYPE_PAGE,
            $page->getId(),
            \Magento\VersionsCms\Model\Increment::LEVEL_VERSION
        );

        return $this;
    }
}
