<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model;

/**
 * Class VersionHistoryInterface
 *
 * @api
 */
interface VersionHistoryInterface
{
    /**
     * @return int
     */
    public function getMaximumInDB();

    /**
     * @param int $maximumVersions
     * @return void
     */
    public function setMaximumInDB($maximumVersions);

    /**
     * @return int
     */
    public function getCurrentId();

    /**
     * @param int $versionId
     * @return void
     */
    public function setCurrentId($versionId);
}
