<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Test\GraphQl;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Quote\Model\QuoteFactory;

/**
 *  Class use to test Giftcard Redeem using GraphQL
 */
class RedeemGiftCardGraphQlTest extends GraphQlAbstract
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var mixed
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteFactory = $objectManager->get(QuoteFactory::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testRedeemGiftCardForReCaptchaFailure(): void
    {
        $this->expectExceptionMessage('ReCaptcha validation failed, please try again');
        $query = $this->prepareQuery();

        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testRedeemGiftCardForReCaptchaSuccess(): void
    {
        $query = $this->prepareQuery();
        $result = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('redeemGiftCardBalanceAsStoreCredit', $result);
    }

    /**
     * @return string
     */
    private function prepareQuery(): string
    {
        $giftCardCode = "giftcardaccount_fixture";

        $query = <<<QUERY
            mutation {
        redeemGiftCardBalanceAsStoreCredit(
            input: {
            gift_card_code: "{$giftCardCode}"
             }
        ) {
            balance {
                currency
        value
        }
        code
        expiration_date
        }
        }
        QUERY;

        return $query;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap =
            [
                'Authorization' => 'Bearer ' . $customerToken,
                'token' =>  $customerToken,
                'X-ReCaptcha' => 'test'
            ];

        return $headerMap;
    }
}
