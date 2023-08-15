<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Api;

use Exception;
use Magento\Framework\Webapi\Exception as HTTPExceptionCodes;
use Magento\QuickCheckout\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\Webapi\Adapter\Rest\RestClient;
use Magento\TestFramework\TestCase\WebapiAbstract;

class StorefrontAccountRepositoryTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/quick-checkout/storefront/has-account';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp() : void
    {
        $this->_markTestAsRestOnly();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Test shopper has account
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 1
     *
     * @return void
     */
    public function testShopperHasAccount()
    {
        $message = $this->buildMessage('customer@example.com');
        $token = $this->buildToken($message, $this->getSigningSecret());
        $this->invokeApi($message, $token);
    }

    /**
     * Test shopper has not account
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 1
     *
     * @return void
     */
    public function testShopperHasNotAccount()
    {
        $message = $this->buildMessage('shopper-without-account@example.com');
        $token = $this->buildToken($message, $this->getSigningSecret());
        try {
            $this->invokeApi($message, $token);
        } catch (Exception $e) {
            $this->assertEquals(HTTPExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
        }
    }

    /**
     * Test request without token
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 1
     *
     * @return void
     */
    public function testRequestWithoutToken()
    {
        $message = $this->buildMessage('customer@example.com');
        try {
            $this->invokeApi($message);
        } catch (Exception $e) {
            $this->assertEquals(HTTPExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
        }
    }

    /**
     * Test request without token
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 1
     *
     * @return void
     */
    public function testRequestInvalidToken()
    {
        $message = $this->buildMessage('customer@example.com');
        $invalidSigningSecret = $this->getSigningSecret() . '-invalid-secret';
        $token = $this->buildToken($message, $invalidSigningSecret);
        try {
            $this->invokeApi($message, $token);
        } catch (Exception $e) {
            $this->assertEquals(HTTPExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
        }
    }

    /**
     * Test request with invalid payload
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 1
     *
     * @return void
     */
    public function testRequestInvalidPayload()
    {
        $message = ["invalid_field" => "invalid_value"];
        $token = $this->buildToken($message, $this->getSigningSecret());
        try {
            $this->invokeApi($message, $token);
        } catch (Exception $e) {
            $this->assertEquals(HTTPExceptionCodes::HTTP_BAD_REQUEST, $e->getCode());
        }
    }

    /**
     * Test extension is disabled
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture   default/payment/quick_checkout/active 0
     *
     * @return void
     */
    public function testExtensionIsDisabled()
    {
        $message = $this->buildMessage('customer@example.com');
        $token = $this->buildToken($message, $this->getSigningSecret());
        try {
            $this->invokeApi($message, $token);
        } catch (Exception $e) {
            $this->assertEquals(HTTPExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
        }
    }

    /**
     * @param array $message
     * @param string|null $hmacHeader
     * @return mixed
     */
    private function invokeApi(array $message, ?string $hmacHeader = null)
    {
        $headers = ["Content-Type: application/json"];
        if ($hmacHeader !== null) {
            $headers[] = "X-Bolt-Hmac-Sha256: " . $hmacHeader;
        }
        /** @var $curlClient RestClient */
        $curlClient = $this->objectManager->get(RestClient::class);

        return $curlClient->post(self::RESOURCE_PATH, $message, $headers);
    }

    /**
     * @param string $message
     * @param string $signingSecret
     * @return string
     */
    private function buildToken(array $message, string $signingSecret) : string
    {
        return base64_encode(hash_hmac('sha256', json_encode($message), $signingSecret, true));
    }

    private function buildMessage(string $email): array
    {
        return ['email' => $email];
    }

    /**
     * @return string
     */
    private function getSigningSecret() : string
    {
        /** @var Config */
        $config = $this->objectManager->create(Config::class);

        return $config->getSigningSecret();
    }
}
