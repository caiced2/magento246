<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Api;

use Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface;
use Magento\Framework\DataObject;

/**
 * Contains the results of the configuration validation for each setting
 */
class ConfigurationCheckResult extends DataObject implements ConfigurationCheckResultInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getTechnicalServiceAccountConfigured(): bool
    {
        return $this->getData(self::TECHNICAL_SERVICE_ACCOUNT_CONFIGURED);
    }

    /**
     * @inheritDoc
     */
    public function getTechnicalServiceAccountCanConnectToIoEvents(): bool
    {
        return $this->getData(self::TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT);
    }

    /**
     * @inheritDoc
     */
    public function getProviderIdConfigured(): string
    {
        return $this->getData(self::PROVIDER_ID_CONFIGURED);
    }

    /**
     * @inheritDoc
     */
    public function getProviderIdValid(): bool
    {
        return $this->getData(self::PROVIDER_ID_VALID);
    }
}
