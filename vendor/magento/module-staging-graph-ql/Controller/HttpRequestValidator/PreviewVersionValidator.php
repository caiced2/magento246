<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StagingGraphQl\Controller\HttpRequestValidator;

use Exception;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GraphQl\Controller\HttpRequestValidatorInterface;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionManager;

/**
 * Validate the Preview Version
 */
class PreviewVersionValidator implements HttpRequestValidatorInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UpdateRepository
     */
    private $updateRepository;

    /**
     * @param VersionManager $versionManager
     * @param UpdateRepository $updateRepository
     */
    public function __construct(
        VersionManager $versionManager,
        Updaterepository $updateRepository
    ) {
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
    }

    /**
     * Validate the VersionManager version matches header value
     *
     * @param HttpRequestInterface $request
     * @return void
     * @throws GraphQlInputException
     */
    public function validate(HttpRequestInterface $request): void
    {
        $headerValue = $request->getHeader('Preview-Version');
        if (!empty($headerValue)) {
            if (!$this->isValidTimestamp($headerValue)) {
                throw new GraphQlInputException(__('Preview-Version must be a valid timestamp.'));
            }
            try {
                $this->updateRepository->getVersionMaxIdByTime((int) $headerValue);
            } catch (Exception $e) {
                throw new GraphQlInputException(__('Preview-Version must be a valid timestamp.'));
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
