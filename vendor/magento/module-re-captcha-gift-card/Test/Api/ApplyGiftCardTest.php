<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Test\Api;

use Magento\Framework\App\MutableScopeConfig;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;

/**
 * Test to validate ReCaptCha while applying GIFTCARD using  APIs
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
// phpcs:disable Generic.Files.LineLength.TooLong
class ApplyGiftCardTest extends WebapiAbstract
{
    private const API_ROUTE = '/V1/carts/mine/giftCards';
    private const API_ROUTE_GUEST = '/V1/carts/guest-carts/%s/giftCards';
    private const RECAPTCHA_HEADER = 'X-ReCaptcha: test';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var MutableScopeConfig
     */
    private $mutableScopeConfig;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_markTestAsRestOnly();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->mutableScopeConfig = $this->objectManager->get(MutableScopeConfig::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture base_website customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testGiftCardForCustomer(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('{"message":"ReCaptcha validation failed, please try again"}');

        $token = $this->generateCustomerToken();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $cartId = $quote->getId();
        $cartManagementService = $this->objectManager->create(
            CartManagementInterface::class
        );
        $cartManagementService->assignCustomer($cartId, 1, 1);

        $requestData = [
            'cartId' => $cartId,
            'giftCardAccountData' => [
                'giftCards' => ['giftcardaccount_fixture']]
        ];

        $serviceInfo = $this->generateServiceInfo(self::API_ROUTE, $token);
        $this->_webApiCall($serviceInfo, $requestData);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote.php
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testGiftCardForGuest(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('{"message":"ReCaptcha validation failed, please try again"}');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(Quote::class)
            ->load('test_order_1', 'reserved_order_id');
        $cartId = $quote->getId();

        $requestData = [
            'cartId' => 1,
            'giftCardAccountData' => [
                'giftCards' => ['giftcardaccount_fixture']
            ]
        ];
        $serviceInfo  = $this->generateServiceInfo(sprintf(self::API_ROUTE_GUEST, $cartId));

        $this->_webApiCall($serviceInfo, $requestData);
    }
    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture base_website customer/captcha/enable 1
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testGiftCardForCustomerReCaptchaSuccess(): void
    {
        $this->setConfig();
        $token = $this->generateCustomerToken();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $cartId = $quote->getId();
        $getMaskedQuoteIdByReservedOrderId = $this->objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $maskedQuoteId = $getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $cartManagementService = $this->objectManager->create(
            CartManagementInterface::class
        );
        $cartManagementService->assignCustomer($cartId, 1, 1);

        $requestData = [
            'cartId' => $maskedQuoteId,
            'giftCardAccountData' => [
                'giftCards' => ['giftcardaccount_fixture']]
        ];

        $serviceInfo = $this->generateServiceInfo(self::API_ROUTE, $token);
        $serviceInfo['rest']['headers'] = [self::RECAPTCHA_HEADER];

        $this->assertTrue($this->_webApiCall($serviceInfo, $requestData));
        $this->resetConfig();
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote.php
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     */
    public function testGiftCardForGuestReCaptchaSuccess(): void
    {
        $this->setConfig();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No such entity');

        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class)->load('test01', 'reserved_order_id');
        $cartId = $quote->getId();

        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMaskFactory = $this->objectManager
            ->create(\Magento\Quote\Model\QuoteIdMaskFactory::class);
        $quoteIdMask = $quoteIdMaskFactory->create();
        $quoteIdMask->load($cartId, 'quote_id');
        $cartId = $quoteIdMask->getMaskedId();

        $requestData = [
            'cartId' => $cartId,
            'giftCardAccountData' => [
                'giftCards' => ['giftcardaccount_fixture']
            ]
        ];

        $serviceInfo  = $this->generateServiceInfo(sprintf(self::API_ROUTE_GUEST, $cartId));
        $serviceInfo['rest']['headers'] = [self::RECAPTCHA_HEADER];
        $this->_webApiCall($serviceInfo, $requestData);
        $this->resetConfig();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function generateCustomerToken() :string
    {
        /** @var \Magento\Integration\Api\CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = $this->objectManager->create(
            \Magento\Integration\Api\CustomerTokenServiceInterface::class
        );

        return $customerTokenService->createCustomerAccessToken(
            'customer@example.com',
            'password'
        );
    }

    /**
     * @param string $apiUrl
     * @param string|null $token
     * @return array[]
     */
    private function generateServiceInfo(string $apiUrl, string $token = null): array
    {
        return  [
            'rest' => [
                'resourcePath' => $apiUrl,
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token
            ],
        ];
    }

    /**
     *
     */
    private function setConfig(): void
    {
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/public_key',
            '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
            ScopeInterface::SCOPE_WEBSITE
        );
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/private_key',
            '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     *
     */
    private function resetConfig(): void
    {
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/public_key',
            null,
            ScopeInterface::SCOPE_WEBSITE
        );
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/private_key',
            null,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
