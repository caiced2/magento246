<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig as Config;

class TagManagerConfig implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $tagManagerConfig;

    /**
     * @param Config $tagManagerConfig
     */
    public function __construct(Config $tagManagerConfig)
    {
        $this->tagManagerConfig = $tagManagerConfig;
    }

    /**
     * Get account type
     *
     * @return string
     */
    public function getAccountType(): string
    {
        return $this->tagManagerConfig->getAccountType();
    }

    /**
     * Get container id used in tag manager
     *
     * @return string
     */
    public function getContainerId(): string
    {
        return $this->tagManagerConfig->getContainerId();
    }

    /**
     * Get Measurement Id (GA4)
     *
     * @return string
     */
    public function getMeasurementId(): string
    {
        return $this->tagManagerConfig->getMeasurementId();
    }
}
