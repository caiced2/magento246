<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadataFactory;
use Magento\AdobeIoEventsClient\Model\IOEventsApi\ApiRequestExecutor;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Interaction with Event Metadata on the IO Events API
 *
 * @api
 * @since 1.1.0
 */
class EventMetadataClient
{
    private const XML_PATH_EVENTS_CREATION_URL = AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_EVENTS_CREATION_URL;
    private const XML_PATH_EVENTS_TYPE_LIST_URL = AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_EVENTS_TYPE_LIST_URL;
    private const XML_PATH_EVENTS_TYPE_DELETE_URL =
        AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_EVENTS_TYPE_DELETE_URL;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var ApiRequestExecutor
     */
    private ApiRequestExecutor $requestExecutor;

    /**
     * @var EventMetadataFactory
     */
    private EventMetadataFactory $eventMetadataFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param ApiRequestExecutor $requestExecutor
     * @param EventMetadataFactory $eventMetadataFactory
     * @param Json $json
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        ApiRequestExecutor $requestExecutor,
        EventMetadataFactory $eventMetadataFactory,
        Json $json
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->requestExecutor = $requestExecutor;
        $this->eventMetadataFactory = $eventMetadataFactory;
        $this->json = $json;
    }

    /**
     * Calls the api to create event metadata
     *
     * @param EventProviderInterface $provider
     * @param EventMetadataInterface $eventMetadata
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws InputException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function createEventMetadata(
        EventProviderInterface $provider,
        EventMetadataInterface $eventMetadata
    ) {
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataCreationUri($configuration, $provider);

        $params = [
            'json' => $eventMetadata->jsonSerialize()
        ];

        $response = $this->requestExecutor->executeRequest(
            ApiRequestExecutor::POST,
            $uri,
            $params
        );

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(__("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 201) {
            throw new InputException(__($response->getReasonPhrase()));
        }
    }

    /**
     * Retrieves metadata for registered events
     *
     * @param EventProviderInterface $provider
     * @return array
     * @throws AuthorizationException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function listRegisteredEventMetadata(EventProviderInterface $provider): array
    {
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataListUri($configuration, $provider);

        $response = $this->requestExecutor->executeRequest(ApiRequestExecutor::GET, $uri);

        if ($response->getStatusCode() == 404) {
            throw new NotFoundException(__("EventMetadata list was not found"));
        }

        $data = $this->json->unserialize($response->getBody()->getContents());
        $eventMetadataList = [];
        foreach ($data["_embedded"]["eventmetadata"] as $eventMetadataData) {
            $eventType = $this->eventMetadataFactory->create(["data" => $eventMetadataData]);

            $eventMetadataList[] = $eventType;
        }

        return $eventMetadataList;
    }

    /**
     * Calls the API to delete the specified event metadata
     *
     * @param EventProviderInterface $provider
     * @param EventMetadataInterface $eventType
     * @return bool
     * @throws AuthorizationException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function deleteEventMetadata(
        EventProviderInterface $provider,
        EventMetadataInterface $eventType
    ): bool {
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = $this->getEventMetadataDeleteUri($configuration, $provider, $eventType->getEventCode());

        $response = $this->requestExecutor->executeRequest(ApiRequestExecutor::DELETE, $uri);

        return $response->getStatusCode() == 204;
    }

    /**
     * Compute Event Metadata Delete URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProviderInterface $provider
     * @param string $eventCode
     * @return string
     */
    private function getEventMetadataDeleteUri(
        AdobeConsoleConfiguration $configuration,
        EventProviderInterface $provider,
        string $eventCode
    ): string {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}", "#{event_code}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId(),
                $eventCode
            ],
            $this->configurationProvider->getApiUrl() . '/' .
                $this->configurationProvider->getScopeConfig(
                    self::XML_PATH_EVENTS_TYPE_DELETE_URL,
                    AdobeIOConfigurationProvider::SCOPE_STORE
                )
        );
    }

    /**
     * Compute Event Metadata List URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProviderInterface $provider
     * @return string
     */
    private function getEventMetadataListUri(
        AdobeConsoleConfiguration $configuration,
        EventProviderInterface $provider
    ): string {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId()
            ],
            $this->configurationProvider->getApiUrl() . '/' .
                $this->configurationProvider->getScopeConfig(
                    self::XML_PATH_EVENTS_TYPE_LIST_URL,
                    AdobeIOConfigurationProvider::SCOPE_STORE
                )
        );
    }

    /**
     * Compute Event Metadata Creation URI
     *
     * @param AdobeConsoleConfiguration $configuration
     * @param EventProviderInterface $provider
     * @return string
     */
    private function getEventMetadataCreationUri(
        AdobeConsoleConfiguration $configuration,
        EventProviderInterface $provider
    ): string {
        return str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}", "#{provider_id}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId(),
                $provider->getId()
            ],
            $this->configurationProvider->getApiUrl() . '/' .
                $this->configurationProvider->getScopeConfig(
                    self::XML_PATH_EVENTS_CREATION_URL,
                    AdobeIOConfigurationProvider::SCOPE_STORE
                )
        );
    }
}
