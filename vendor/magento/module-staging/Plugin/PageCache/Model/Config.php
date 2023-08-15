<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\PageCache\Model;

/**
 * Plugin for Page Cache config.
 */
class Config
{
    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    protected $versionManager;

    /**
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(\Magento\Staging\Model\VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\PageCache\Model\Config $subject
     * @param bool $isEnabled
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsEnabled(\Magento\PageCache\Model\Config $subject, $isEnabled)
    {
        return $this->versionManager->isPreviewVersion() ? false : $isEnabled;
    }
}
