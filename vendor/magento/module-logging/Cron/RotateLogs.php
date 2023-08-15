<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Cron;

class RotateLogs
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_coreConfig;

    /**
     * Flag model factory
     *
     * @var \Magento\Logging\Model\FlagFactory
     */
    protected $_flagFactory;

    /**
     * @var \Magento\Logging\Model\ResourceModel\EventFactory
     */
    protected $eventFactory;

    /**
     * @param \Magento\Logging\Model\ResourceModel\EventFactory $eventFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param \Magento\Logging\Model\FlagFactory $flagFactory
     */
    public function __construct(
        \Magento\Logging\Model\ResourceModel\EventFactory $eventFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\Logging\Model\FlagFactory $flagFactory
    ) {
        $this->eventFactory = $eventFactory;
        $this->_coreConfig = $coreConfig;
        $this->_flagFactory = $flagFactory;
    }

    /**
     * Cron job for logs rotation
     *
     * @return void
     */
    public function execute()
    {
        $lastRotationFlag = $this->_flagFactory->create()->loadSelf();
        $lastRotationTime = $lastRotationFlag->getFlagData();
        $rotationFrequency = 3600 * 24 * (int)$this->_coreConfig->getValue('system/rotation/frequency', 'default');
        if (!$lastRotationTime || $lastRotationTime < time() - $rotationFrequency) {
            $this->eventFactory->create()->rotate(
                3600 * 24 * (int)$this->_coreConfig->getValue('system/rotation/lifetime', 'default')
            );
        }
        $lastRotationFlag->setFlagData(time())->save();
    }
}
