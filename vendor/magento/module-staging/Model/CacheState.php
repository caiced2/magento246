<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\App\Cache\StateInterface;

/**
 * Class CacheState
 */
class CacheState implements StateInterface
{
    /**
     * @var StateInterface
     */
    private $state;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var boolean[]
     */
    private $cacheTypes;

    /**
     * CacheState constructor.
     *
     * @param StateInterface $state
     * @param VersionManager $versionManager
     * @param array $cacheTypes
     */
    public function __construct(
        StateInterface $state,
        VersionManager $versionManager,
        $cacheTypes = []
    ) {
        $this->state = $state;
        $this->versionManager = $versionManager;
        $this->cacheTypes = $cacheTypes;
    }

    /**
     * Whether a cache type is enabled at the moment or not
     *
     * @param string $cacheType
     * @return bool
     */
    public function isEnabled($cacheType)
    {
        if ($this->versionManager->isPreviewVersion() && isset($this->cacheTypes[$cacheType])) {
            return $this->cacheTypes[$cacheType];
        } else {
            return $this->state->isEnabled($cacheType);
        }
    }

    /**
     * Enable/disable a cache type in run-time
     *
     * @param string $cacheType
     * @param bool $isEnabled
     * @return void
     */
    public function setEnabled($cacheType, $isEnabled)
    {
        $this->state->setEnabled($cacheType, $isEnabled);
    }

    /**
     * Save the current statuses (enabled/disabled) of cache types to the persistent storage
     *
     * @return void
     */
    public function persist()
    {
        $this->state->persist();
    }
}
