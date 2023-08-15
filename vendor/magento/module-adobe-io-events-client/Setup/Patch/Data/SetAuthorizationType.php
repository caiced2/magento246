<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Setup\Patch\Data;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Config\Source\AuthorizationType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Sets authorization type if configuration was already set before the module upgrade.
 */
class SetAuthorizationType implements DataPatchInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private WriterInterface $writer,
        private AdobeIOConfigurationProvider $configurationProvider,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sets authorization type as JWT if configuration was already set before the module upgrade.
     *
     * Does nothing if the authorization type was already set.
     *
     * {@inheritDoc}
     */
    public function apply()
    {
        try {
            $authType = $this->scopeConfig->getValue(
                AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE
            );
            if (empty($authType)) {
                $this->configurationProvider->getConfiguration();
                $this->configurationProvider->getPrivateKey();
                $this->writer->save(
                    AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE,
                    AuthorizationType::JWT
                );
            }
        } catch (LocalizedException $exception) {
            $this->logger->error(
                'Failed to update authorization type to Adobe I/O events: ' . $exception->getMessage()
            );
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
