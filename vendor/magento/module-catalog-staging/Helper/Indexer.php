<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Helper;

use Magento\Staging\Model\VersionManager;

/**
 * Class Indexer
 */
class Indexer
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * Indexer constructor.
     *
     * @param VersionManager $versionManager
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Indexer\Model\Indexer $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsScheduled(
        \Magento\Indexer\Model\Indexer $subject,
        $result
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            $result = true;
        }

        return $result;
    }
}
