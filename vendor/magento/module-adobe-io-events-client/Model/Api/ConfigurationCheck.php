<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Api;

use Exception;
use Magento\AdobeIoEventsClient\Api\ConfigurationCheckInterface;
use Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterface;
use Magento\AdobeIoEventsClient\Api\ConfigurationCheckResultInterfaceFactory;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\AdobeIoEventsClient\Model\IOEventsApi\ApiRequestExecutor;
use Magento\Framework\Exception\NotFoundException;

/**
 * Validates required elements of the configuration
 */
class ConfigurationCheck implements ConfigurationCheckInterface
{
    private const XML_PATH_PROVIDER_LIST_URL = AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_PROVIDER_LIST_URL;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var ConfigurationCheckResultInterfaceFactory
     */
    private ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory;

    /**
     * @var EventMetadataClient
     */
    private EventMetadataClient $eventMetadataClient;

    /**
     * @var ApiRequestExecutor
     */
    private ApiRequestExecutor $requestExecutor;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory
     * @param EventMetadataClient $eventMetadataClient
     * @param ApiRequestExecutor $requestExecutor
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        ConfigurationCheckResultInterfaceFactory $configurationCheckResultFactory,
        EventMetadataClient $eventMetadataClient,
        ApiRequestExecutor $requestExecutor
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->configurationCheckResultFactory = $configurationCheckResultFactory;
        $this->eventMetadataClient = $eventMetadataClient;
        $this->requestExecutor = $requestExecutor;
    }

    /**
     * @inheritDoc
     */
    public function checkConfiguration(): ConfigurationCheckResultInterface
    {
        $data = [];
        $status = 'ok';

        try {
            $this->configurationProvider->getPrivateKey();
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CONFIGURED] = true;
        } catch (NotFoundException $exception) {
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CONFIGURED] = false;
            $status = 'error';
        }

        if ($this->canServiceAccountConnect()) {
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT] = true;
        } else {
            $data[ConfigurationCheckResultInterface::TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT] = false;
            $status = 'error';
        }

        $eventProvider = $this->configurationProvider->getProvider();

        if ($eventProvider === null) {
            $status = 'error';
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_CONFIGURED] = '';
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = false;
        } else {
            $data[ConfigurationCheckResultInterface::PROVIDER_ID_CONFIGURED] = $eventProvider->getId();

            try {
                $this->eventMetadataClient->listRegisteredEventMetadata($eventProvider);
                $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = true;
            } catch (Exception $e) {
                $data[ConfigurationCheckResultInterface::PROVIDER_ID_VALID] = false;
                $status = 'error';
            }
        }

        $data[ConfigurationCheckResultInterface::STATUS] = $status;

        return $this->configurationCheckResultFactory->create(['data' => $data ]);
    }

    /**
     * Checks if the technical service account can connect to the provider endpoint
     *
     * @return bool
     */
    private function canServiceAccountConnect(): bool
    {
        try {
            $configuration = $this->configurationProvider->getConfiguration();

            $uri = str_replace(
                ["#{ims_org_id}"],
                [
                    $configuration->getProject()->getOrganization()->getId(),
                ],
                $this->configurationProvider->getScopeConfig(self::XML_PATH_PROVIDER_LIST_URL)
            );
            $uri = $this->configurationProvider->getApiUrl() . '/' . $uri;

            $response = $this->requestExecutor->executeRequest(ApiRequestExecutor::GET, $uri);

            if ($response->getStatusCode() != 200) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
