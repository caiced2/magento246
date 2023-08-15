<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Block\Adminhtml\Update;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRuleStaging\Model\Staging\PreviewStoreIdResolver;
use Magento\Staging\Block\Adminhtml\Update\Entity\StoreIdProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider;
use Magento\Staging\Model\VersionManager;

/**
 * Cart price rule staging preview store id provider
 */
class StoreIdProvider implements StoreIdProviderInterface
{
    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var PreviewStoreIdResolver
     */
    private $previewStoreIdResolver;

    /**
     * @var IdProvider
     */
    private $previewUpdateIdProvider;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param RequestInterface $request
     * @param RuleRepositoryInterface $ruleRepository
     * @param PreviewStoreIdResolver $previewStoreIdResolver
     * @param IdProvider $previewUpdateIdProvider
     * @param VersionManager $versionManager
     */
    public function __construct(
        RequestInterface $request,
        RuleRepositoryInterface $ruleRepository,
        PreviewStoreIdResolver $previewStoreIdResolver,
        IdProvider $previewUpdateIdProvider,
        VersionManager $versionManager
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->request = $request;
        $this->previewStoreIdResolver = $previewStoreIdResolver;
        $this->previewUpdateIdProvider = $previewUpdateIdProvider;
        $this->versionManager = $versionManager;
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        $oldUpdateId = $this->versionManager->getCurrentVersion()->getId();
        try {
            $this->versionManager->setCurrentVersionId($this->previewUpdateIdProvider->getUpdateId());
            $rule = $this->ruleRepository->getById($this->request->getParam('id'));
            $this->versionManager->setCurrentVersionId($oldUpdateId);
            $storeId = $this->previewStoreIdResolver->execute($rule->getWebsiteIds() ?? []);
        } catch (NoSuchEntityException $e) {
            $this->versionManager->setCurrentVersionId($oldUpdateId);
            $storeId = null;
        }
        return $storeId;
    }
}
