<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model;

use InvalidArgumentException;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\ConfigurationFactory;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\Data\PrivateKey;
use Magento\AdobeIoEventsClient\Model\Data\PrivateKeyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Provider for the Adobe IO configuration data
 *
 * @api
 * @since 1.1.0
 */
class AdobeIOConfigurationProvider
{
    private const XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID = "adobe_io_events/integration/provider_id";
    private const XML_PATH_ADOBE_IO_EVENT_INSTANCE_ID = "adobe_io_events/integration/instance_id";
    private const XML_PATH_ADOBE_IO_SERVICE_ACCOUNT_PRIVATE_KEY = "adobe_io_events/integration/private_key";
    private const XML_PATH_ADOBE_IO_EVENT_CONSOLE_CONFIGURATION = "adobe_io_events/integration/workspace_configuration";
    private const XML_PATH_ADOBE_IO_EVENT_PROVIDER_METADATA =
        "adobe_io_events/integration/adobe_io_event_provider_metadata";
    public const XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE = "adobe_io_events/integration/authorization_type";
    public const XML_PATH_ADOBE_IO_PROVIDER_URL = 'adobe_io_events/integration/adobe_io_provider_url';
    public const XML_PATH_ADOBE_IO_PROVIDER_LIST_URL = 'adobe_io_events/integration/adobe_io_provider_list_url';
    public const XML_PATH_ADOBE_IO_EVENTS_CREATION_URL = 'adobe_io_events/integration/adobe_io_event_creation_url';
    public const XML_PATH_ADOBE_IO_EVENTS_TYPE_LIST_URL = 'adobe_io_events/integration/adobe_io_event_type_list_url';
    public const XML_PATH_ADOBE_IO_EVENTS_TYPE_DELETE_URL =
        'adobe_io_events/integration/adobe_io_event_type_delete_url';
    public const XML_ADOBE_IO_PATH_ENVIRONMENT = 'adobe_io_events/integration/adobe_io_environment';
    public const XML_PATH_IMS_JWT_EXPIRATION_INTERVAL = "adobe_io_events/integration/ims_jwt_expiration_interval";

    public const SCOPE_STORE = ScopeInterface::SCOPE_STORE;
    public const ENV_STAGING = 'staging';

    private const API_URL_PROD = 'https://api.adobe.io';
    private const API_URL_STAGE = 'https://api-stage.adobe.io';

    /**
     * @var EventProviderFactory
     */
    private EventProviderFactory $eventProviderFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ConfigurationFactory
     */
    private ConfigurationFactory $configurationFactory;

    /**
     * @var PrivateKeyFactory
     */
    private PrivateKeyFactory $privateKeyFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @var AdobeConsoleConfiguration|null
     */
    private ?AdobeConsoleConfiguration $configuration = null;

    /**
     * @param EventProviderFactory $eventProviderFactory
     * @param Json $json
     * @param ConfigurationFactory $configurationFactory
     * @param PrivateKeyFactory $privateKeyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     */
    public function __construct(
        EventProviderFactory $eventProviderFactory,
        Json $json,
        ConfigurationFactory $configurationFactory,
        PrivateKeyFactory $privateKeyFactory,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer
    ) {
        $this->eventProviderFactory = $eventProviderFactory;
        $this->json = $json;
        $this->configurationFactory = $configurationFactory;
        $this->privateKeyFactory = $privateKeyFactory;
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
    }

    /**
     * Retrieve Instance ID
     *
     * @return string
     * @throws NotFoundException
     */
    public function retrieveInstanceId(): string
    {
        $instanceId = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_EVENT_INSTANCE_ID);

        if (is_array($instanceId) || !$instanceId) {
            throw new NotFoundException(__("Instance ID not found in configuration"));
        }

        return $instanceId;
    }

    /**
     * Retrieve Event Provider from the scope
     *
     * @return EventProviderInterface|null
     */
    public function getProvider(): ?EventProviderInterface
    {
        $providerId = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID);

        if (is_array($providerId) || !$providerId) {
            return null;
        }

        return $this->eventProviderFactory->create(['data' => ['id' => $providerId]]);
    }

    /**
     * Takes event metadata from the Adobe IO API, filters it by configured providers, and converts to provider objects
     *
     * @param array $eventMetadata
     * @return EventProviderInterface[]
     */
    public function listProvidersFromApiMetadata(array $eventMetadata): array
    {
        $providers = [];
        $eventProviderMetadata = $this->getEventProviderMetadata();
        foreach ($eventMetadata as $eventMetadataData) {
            if (!$eventProviderMetadata || $eventProviderMetadata == $eventMetadataData["provider_metadata"]) {
                $providers[] = $this->eventProviderFactory->create(["data" => $eventMetadataData]);
            }
        }

        return $providers;
    }

    /**
     * Helper function to check if a provider has been configured at all
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        $providerId = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID);

        if (is_array($providerId) || !$providerId) {
            return false;
        }
        return true;
    }

    /**
     * Get service account private key
     *
     * @return PrivateKey
     * @throws NotFoundException
     */
    public function getPrivateKey(): PrivateKey
    {
        $privateKeyData = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_SERVICE_ACCOUNT_PRIVATE_KEY);

        if (is_array($privateKeyData) || !$privateKeyData) {
            throw new NotFoundException(__("Private Key not found in configuration"));
        }

        $privateKey = $this->privateKeyFactory->create();
        $privateKey->setData($privateKeyData);

        return $privateKey;
    }

    /**
     * Get populated configuration object
     *
     * @return AdobeConsoleConfiguration
     * @throws NotFoundException
     * @throws InvalidConfigurationException
     */
    public function getConfiguration(): AdobeConsoleConfiguration
    {
        if ($this->configuration === null) {
            $configuration = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_EVENT_CONSOLE_CONFIGURATION);

            if (is_array($configuration) || !$configuration) {
                throw new NotFoundException(__("Could not find Adobe I/O Workspace Configuration information"));
            }

            try {
                $data = $this->json->unserialize($configuration);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidConfigurationException(
                    __('Could not fetch Adobe I/O Workspace Configuration: %1', $exception->getMessage())
                );
            }
            $this->configuration = $this->configurationFactory->create($data);
        }

        return $this->configuration;
    }

    /**
     * Update the stored provider configuration
     *
     * @param EventProviderInterface $eventProvider
     * @return void
     */
    public function saveProvider(EventProviderInterface $eventProvider)
    {
        $this->writer->save(
            self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_ID,
            $eventProvider->getId()
        );
    }

    /**
     * Get the provider metadata config setting
     *
     * @return string
     */
    public function getEventProviderMetadata(): ?string
    {
        $value = $this->getScopeConfig(self::XML_PATH_ADOBE_IO_EVENT_PROVIDER_METADATA);

        if (is_array($value) || !$value) {
            return null;
        }

        return $value;
    }

    /**
     * Get the base url for the current environment
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->getScopeConfig(self::XML_ADOBE_IO_PATH_ENVIRONMENT) === self::ENV_STAGING
            ? self::API_URL_STAGE
            : self::API_URL_PROD;
    }

    /**
     * Fetches a value from the scope config
     *
     * @param string $path
     * @param string $scope
     * @return mixed
     */
    public function getScopeConfig(string $path, string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue($path, $scope);
    }
}
