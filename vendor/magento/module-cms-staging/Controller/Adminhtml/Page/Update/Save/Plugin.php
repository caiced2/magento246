<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save;

use Magento\CmsStaging\Controller\Adminhtml\Page\Update\Save;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Psr\Log\LoggerInterface;

class Plugin
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        LoggerInterface $logger
    ) {
        $this->updateRepository = $updateRepository;
        $this->logger = $logger;
    }

    /**
     * Entry point for the plugin to updateStart time.
     *
     * @param Save $subject
     * @return void
     */
    public function beforeExecute(Save $subject)
    {
        try {
            $customTheme = $subject->getRequest()->getPostValue('custom_theme');
            if ($this->isValidCustomTheme($customTheme)) {
                $staging = $subject->getRequest()->getPostValue('staging');
                switch ($staging['mode']) {
                    case 'assign':
                        $update = $this->updateRepository->get($staging['select_id']);
                        $startTime = $update->getStartTime();
                        break;

                    case 'save':
                        $startTime = !empty($staging['start_time']) ? $staging['start_time'] : null;
                        break;

                    default:
                        $startTime = false;
                        break;
                }
                if ($startTime !== false) {
                    $date = new \DateTime($startTime, new \DateTimeZone('UTC'));
                    $subject->getRequest()->setPostValue('custom_theme_from', $date->format('m/d/Y'));
                }
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
