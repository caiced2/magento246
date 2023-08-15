<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Controller\Adminhtml\Page\Save;

use Magento\Cms\Controller\Adminhtml\Page\Save;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Class for updating custom_theme_from field
 */
class Plugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param LoggerInterface $logger
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        LoggerInterface $logger,
        TimezoneInterface $localeDate
    ) {
        $this->logger = $logger;
        $this->localeDate = $localeDate;
    }

    /**
     * Update custom_theme_from field
     *
     * @param Save $subject
     * @return void
     */
    public function beforeExecute(Save $subject)
    {
        try {
            $customTheme = $subject->getRequest()->getPostValue('custom_theme');
            if ($this->isValidCustomTheme($customTheme)) {
                $subject->getRequest()->setPostValue('custom_theme_from', $this->localeDate->formatDate());
            } else {
                $subject->getRequest()->setPostValue('custom_theme_from', null);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Check if custom theme identifier exists and valid
     *
     * @param mixed $customTheme
     * @return bool
     */
    private function isValidCustomTheme($customTheme)
    {
        if ($customTheme !== null && (int) $customTheme > 0) {
            return true;
        }
        return false;
    }
}
