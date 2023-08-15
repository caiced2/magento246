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
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;

/**
 *  Class use to test Apply Giftcard on Cart using GraphQL
 */


class ApplyGiftCardToCartGraphQlTest extends GraphQlAbstract
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteFactory = $objectManager->get(QuoteFactory::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testApplyGiftCardToCartForReCaptchaFailure(): void
    {
        $this->expectExceptionMessage(
            'GraphQL response contains errors: ReCaptcha validation failed, please try again'
        );

        $query = $this->prepareQuery();
        $this->graphQlMutation($query);
    }

    /**
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/giftcardaccount.php
     * @magentoConfigFixture default_store customer/captcha/enable 1
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/giftcard invisible
     */
    public function testApplyGiftCardToCartForReCaptchaSuccess(): void
    {
        $query = $this->prepareQuery();
        $result = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('applyGiftCardToCart', $result);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareQuery(): string
    {
        $giftCardCode = 'giftcardaccount_fixture';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');

        $query = <<<QUERY
        mutation {
          applyGiftCardToCart(input: {cart_id: "{$maskedQuoteId}", gift_card_code: "{$giftCardCode}"}) {
            cart {
              prices {
                grand_total {
                  currency
                  value
                }
                subtotal_excluding_tax {
                  currency
                  value
                }
                subtotal_with_discount_excluding_tax {
                  currency
                  value
                }
              }
              applied_gift_cards {
                code
                applied_balance {
                  currency
                  value
                }
                expiration_date
                current_balance {
                  currency
                  value
                }
              }
            }
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
