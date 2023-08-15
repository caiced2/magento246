<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\Store\Model;

/**
 * Plugin for checker of store base url.
 */
class BaseUrlChecker
{
    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Store\Model\BaseUrlChecker $subject
     * @param bool $result
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsEnabled(\Magento\Store\Model\BaseUrlChecker $subject, $result)
    {
        if ($this->versionManager->isPreviewVersion()) {
            $result = false;
        }

        return $result;
    }
}
