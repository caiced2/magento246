<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\IOEventsApi\ApiRequestExecutor;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Interaction with Event Providers on the IO Events API
 */
class EventProviderClient
{
    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var ApiRequestExecutor
     */
    private ApiRequestExecutor $requestExecutor;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param ApiRequestExecutor $requestExecutor
     * @param Json $json
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        ApiRequestExecutor $requestExecutor,
        Json $json
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->requestExecutor = $requestExecutor;
        $this->json = $json;
    }

    /**
     * Call the API to create an event provider
     *
     * @param string $instanceId
     * @param EventProviderInterface $provider
     * @return array|bool|float|int|mixed|string|null
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws InputException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function createEventProvider(
        string $instanceId,
        EventProviderInterface $provider
    ) {
        $configuration = $this->configurationProvider->getConfiguration();

        $uri = str_replace(
            ["#{ims_org_id}", "#{project_id}", "#{workspace_id}"],
            [
                $configuration->getProject()->getOrganization()->getId(),
                $configuration->getProject()->getId(),
                $configuration->getProject()->getWorkspace()->getId()
            ],
            $this->configurationProvider->getScopeConfig(AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_PROVIDER_URL)
        );
        $uri = $this->configurationProvider->getApiUrl() . '/' . $uri;

        $params = [
            "json" => [
                "instance_id" => $instanceId,
                "label" => $provider->getLabel(),
                "description" => sprintf("%s (Instance %s)", $provider->getDescription(), $instanceId)
            ]
        ];

        $eventProviderMetadata = $this->configurationProvider->getEventProviderMetadata();
        if ($eventProviderMetadata) {
            $params['json']['provider_metadata'] = $eventProviderMetadata;
        }

        $response = $this->requestExecutor->executeRequest(
            ApiRequestExecutor::POST,
            $uri,
            $params
        );

        if ($response->getStatusCode() == 409) {
            throw new AlreadyExistsException(__("An event provider with the same instance ID already exists."));
        }

        if ($response->getStatusCode() == 401) {
            throw new AuthenticationException(__("Access Token is not valid anymore"));
        }

        if ($response->getStatusCode() != 201) {
            throw new InputException(__($response->getReasonPhrase()));
        }

        return $this->json->unserialize($response->getBody()->getContents());
    }
}
