<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update;

use Magento\Staging\Model\VersionHistoryInterface;

/**
 * Class VersionHistory
 */
class VersionHistory implements VersionHistoryInterface
{
    /**
     * @var Flag
     */
    protected $flag;

    /**
     * @var FlagFactory
     */
    protected $flagFactory;

    /**
     * @param FlagFactory $flagFactory
     */
    public function __construct(
        \Magento\Staging\Model\Update\FlagFactory $flagFactory
    ) {
        $this->flagFactory = $flagFactory;
    }

    /**
     * @return int
     */
    public function getMaximumInDB()
    {
        return (int)$this->getFlag()->getMaximumVersionsInDb();
    }

    /**
     * @param int $maximumVersions
     * @return void
     */
    public function setMaximumInDB($maximumVersions)
    {
        $this->getFlag()->setMaximumVersionsInDb($maximumVersions);
        $this->getFlag()->save();
    }

    /**
     * @return int|string
     */
    public function getCurrentId()
    {
        return $this->getFlag()->getCurrentVersionId();
    }

    /**
     * @param int $versionId
     * @return void
     */
    public function setCurrentId($versionId)
    {
        $this->getFlag()->setCurrentVersionId($versionId);
        $this->getFlag()->save();
    }

    /**
     * @return \Magento\Staging\Model\Update\Flag
     */
    protected function getFlag()
    {
        if (!$this->flag) {
            $this->flag = $this->flagFactory->create([]);
            $this->flag->loadSelf();
        }
        return $this->flag;
    }
}
