<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Adminhtml\System\Config;

use InvalidArgumentException;
use Magento\Framework\App\Request\Http;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use RuntimeException;

/**
 * Service class to validate account credentials
 */
class CredentialsValidator
{
    private const PUBLISHABLE_KEY_SEGMENTS_COUNT = 3;
    private const DIVISION_ID_SEGMENT = 1;
    private const DIVISION_ID_INDEX = 'division_id';
    private const PUBLISHABLE_KEY_INDEX = 'publishable_key';

    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $serviceClient;

    /**
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $serviceClient
     */
    public function __construct(
        TransferFactoryInterface $transferFactory,
        ClientInterface $serviceClient
    ) {
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
    }

    /**
     * Validate the credentials
     *
     * @param AccountCredentials $accountCredentials
     * @return void
     * @throws ClientException
     * @throws ConverterException
     */
    public function validate(AccountCredentials $accountCredentials)
    {
        $publishableKeyParts = explode('.', $accountCredentials->getPublishableKey());

        $this->assertPublishableKeyIsValid($publishableKeyParts);

        $request = [
            'uri' => '/v1/merchant/identifiers',
            'method' => Http::METHOD_GET,
            'body' => '',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $accountCredentials->getApiKey(),
            ]
        ];

        $transferObject = $this->transferFactory->create($request);

        $response = $this->serviceClient->placeRequest($transferObject);

        $merchantDivision = $publishableKeyParts[self::DIVISION_ID_SEGMENT];
        $merchantDivisions = $response['merchant_divisions'] ?? [];

        if (empty($merchantDivisions)) {
            throw new RuntimeException('Could not find any merchant division');
        }

        $publishableKeyForDivision = $this->findMatchingPublishableKeyForMerchantDivision(
            $merchantDivision,
            $merchantDivisions
        );

        if ($accountCredentials->getPublishableKey() !== $publishableKeyForDivision) {
            throw new InvalidArgumentException('Publishable key mismatch');
        }
    }

    /**
     * Assert that the format of the provided publishable key is valid
     *
     * @param array $parts
     * @return void
     */
    private function assertPublishableKeyIsValid(array $parts)
    {
        if (count($parts) !== self::PUBLISHABLE_KEY_SEGMENTS_COUNT) {
            throw new InvalidArgumentException('Invalid publishable key');
        }
    }

    /**
     * Returns the publishable key associated to the given merchant division
     *
     * @param string $merchantDivision
     * @param array $merchantDivisions
     * @return string
     */
    private function findMatchingPublishableKeyForMerchantDivision(
        string $merchantDivision,
        array $merchantDivisions
    ): string {
        foreach ($merchantDivisions as $division) {
            $divisionId = $division[self::DIVISION_ID_INDEX] ?? '';

            if ($divisionId === $merchantDivision) {
                return $division[self::PUBLISHABLE_KEY_INDEX] ?? '';
            }
        }

        throw new InvalidArgumentException('Could not find a matching merchant division');
    }
}
