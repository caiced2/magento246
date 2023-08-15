<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutStaging\Plugin;

use Magento\CheckoutStaging\Model\PreviewQuota;
use Magento\CheckoutStaging\Model\PreviewQuotaFactory;
use Magento\CheckoutStaging\Model\PreviewQuotaRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;

class SavePreviewQuotaPlugin
{
    /**
     * @var PreviewQuotaFactory
     */
    private $previewQuotaFactory;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var PreviewQuotaRepository
     */
    private $previewQuotaRepository;

    /**
     * SaveFakeQuotaPlugin constructor.
     * @param VersionManager $versionManager
     * @param PreviewQuotaFactory $previewQuotaFactory
     * @param PreviewQuotaRepository $previewQuotaRepository
     */
    public function __construct(
        VersionManager $versionManager,
        PreviewQuotaFactory $previewQuotaFactory,
        PreviewQuotaRepository $previewQuotaRepository
    ) {
        $this->previewQuotaFactory = $previewQuotaFactory;
        $this->versionManager = $versionManager;
        $this->previewQuotaRepository = $previewQuotaRepository;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param null $result
     * @param CartInterface $quote
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        CartRepositoryInterface $subject,
        $result,
        CartInterface $quote
    ) {
        if ($this->versionManager->isPreviewVersion() && $quote->getId()) {
            /** @var PreviewQuota $previewQuota */
            $previewQuota = $this->previewQuotaFactory->create();
            $previewQuota->setId($quote->getId());

            $this->previewQuotaRepository->save($previewQuota);
        }
    }
}
