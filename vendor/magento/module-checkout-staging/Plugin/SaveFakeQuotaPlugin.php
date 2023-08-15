<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutStaging\Plugin;

use Magento\CheckoutStaging\Model\PreviewQuota;
use Magento\CheckoutStaging\Model\PreviewQuotaFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;

class SaveFakeQuotaPlugin
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
     * SaveFakeQuotaPlugin constructor.
     *
     * @param VersionManager $versionManager
     * @param PreviewQuotaFactory $previewQuotaFactory
     */
    public function __construct(
        VersionManager $versionManager,
        PreviewQuotaFactory $previewQuotaFactory
    ) {
        $this->previewQuotaFactory = $previewQuotaFactory;
        $this->versionManager = $versionManager;
    }

    /**
     * @param CartInterface $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterSave(CartInterface $subject, $result)
    {
        if ($this->versionManager->isPreviewVersion()) {
            /** @var PreviewQuota $previewQuota */
            $previewQuota = $this->previewQuotaFactory->create();
            $previewQuota->load($subject->getId());

            if (!$previewQuota->getId()) {
                $previewQuota->setId($subject->getId());
                $previewQuota->save();
            }
        }

        return $result;
    }
}
