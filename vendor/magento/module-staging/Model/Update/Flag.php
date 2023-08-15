<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Update;

/**
 * Class Flag
 */
class Flag extends \Magento\Framework\Flag
{
    /**
     * Default Config path for maximum versions in DB
     */
    const MAXIMUM_VERSIONS_IN_DB = 'maximum_versions_in_db';

    /**
     * Config path for current version
     */
    const CURRENT_VERSION = 'current_version';

    /**
     * Flag code
     *
     * @var string
     */
    protected $_flagCode = "staging";

    /**
     * @return int
     */
    public function getCurrentVersionId()
    {
        return $this->getStagingFlag(self::CURRENT_VERSION) ?: 1;
    }

    /**
     * @param int $value
     * @return void
     */
    public function setCurrentVersionId($value)
    {
        $this->setStagingFlag(self::CURRENT_VERSION, $value);
    }

    /**
     * @return int
     */
    public function getMaximumVersionsInDb()
    {
        return $this->getStagingFlag(self::MAXIMUM_VERSIONS_IN_DB) ?: 10;
    }

    /**
     * @param int $value
     * @return void
     */
    public function setMaximumVersionsInDb($value)
    {
        $this->setStagingFlag(self::MAXIMUM_VERSIONS_IN_DB, $value);
    }

    /**
     * @param string $key
     * @return null|string
     */
    protected function getStagingFlag($key)
    {
        $value = null;
        if (is_array($this->getFlagData()) && array_key_exists($key, $this->getFlagData())) {
            $value = $this->getFlagData()[$key];
        }
        return $value;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setStagingFlag($key, $value)
    {
        $flagData = is_array($this->getFlagData()) ? $this->getFlagData() : [];
        $flagData[$key] = $value;
        $this->setFlagData($flagData);
    }
}
