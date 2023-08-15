<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use InvalidArgumentException;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\QuickCheckout\Gateway\Http\TransferFactory;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;

/**
 * Service class to add new addresses to bolt account
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AddressService
{
    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $serviceClient;

    /**
     * @var OauthTokenSessionStorage
     */
    private $oauthTokenSessionStorage;

    /**
     * @var ManagerInterface
     */
    private $messages;

    /**
     * @param TransferFactory $transferFactory
     * @param ClientInterface $serviceClient
     * @param OauthTokenSessionStorage $oauthTokenSessionStorage
     * @param ManagerInterface $messages
     */
    public function __construct(
        TransferFactory $transferFactory,
        ClientInterface $serviceClient,
        OauthTokenSessionStorage $oauthTokenSessionStorage,
        ManagerInterface $messages
    ) {
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
        $this->oauthTokenSessionStorage = $oauthTokenSessionStorage;
        $this->messages = $messages;
    }

    /**
     * Add address to bolt wallet
     *
     * @param array $addressInformation
     * @return array
     * @throws ClientException
     * @throws ConverterException
     * @throws InvalidArgumentException
     */
    public function addAddress(array $addressInformation): array
    {
        $customerToken = $this->oauthTokenSessionStorage->retrieve();

        if (!$customerToken) {
            throw new InvalidArgumentException('No valid bolt session token available');
        }

        if (!$customerToken->canManageAccountDetails()) {
            // phpcs:ignore
            $this->messages->addWarningMessage('You were logged out from Bolt due to inactivity. Your order was completed successfully but we were unable to save your shipping details in the Bolt wallet.');
            return [];
        }

        $request = [
            'uri' => '/v1/account/addresses',
            'method' => Http::METHOD_POST,
            'body' => $addressInformation,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'bearer ' . $customerToken->getAccessToken(),
            ]
        ];
        $transferObject = $this->transferFactory->create($request);
        return $this->serviceClient->placeRequest($transferObject);
    }
}
