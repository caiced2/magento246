<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\DateTime;

use Magento\Framework\Stdlib\DateTime\Timezone as StdlibTimezone;
use Magento\Staging\Model\VersionManager;

class Timezone
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @param VersionManager $versionManager
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param StdlibTimezone $subject
     * @param bool $result
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsScopeDateInInterval(StdlibTimezone $subject, $result)
    {
        return $this->versionManager->isPreviewVersion() ? true : $result;
    }
}
