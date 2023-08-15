<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StagingGraphQl\Controller\HttpHeaderProcessor;

use Magento\GraphQl\Controller\HttpHeaderProcessorInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Process Preview-Version header
 */
class PreviewVersionProcessor implements HttpHeaderProcessorInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        VersionManager $versionManager,
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
    }

    /**
     * Process Preview-Version header
     *
     * @param string $headerValue
     */
    public function processHeaderValue(string $headerValue): void
    {
        if (!empty($headerValue)) {
            if ($this->isValidTimestamp($headerValue)) {
                $versionId = $this->updateRepository->getVersionMaxIdByTime((int) $headerValue);
                $this->versionManager->setCurrentVersionId($versionId);
            }
        }
    }

    /**
     * Validate timestamp
     *
     * @param string $timestamp
     * @return bool
     */
    private function isValidTimestamp(string $timestamp)
    {
        return is_numeric($timestamp)
            && $timestamp < PHP_INT_MAX
            && $timestamp > 0;
    }
}
