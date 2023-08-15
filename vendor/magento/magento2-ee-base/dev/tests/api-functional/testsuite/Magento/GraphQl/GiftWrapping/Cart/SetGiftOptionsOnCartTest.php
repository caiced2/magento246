<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\GiftWrapping\Cart;

use Magento\GiftWrapping\Model\ResourceModel\Wrapping as GiftWrappingResource;
use Magento\GiftWrapping\Model\Wrapping as GiftWrapping;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class SetGiftOptionsOnCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var GiftWrapping
     */
    private $giftWrappingModel;

    /**
     * @var GiftWrappingResource
     */
    private $giftWrappingResource;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var string
     */
    private $cartId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->giftWrappingModel = $objectManager->get(GiftWrapping::class);
        $this->giftWrappingResource = $objectManager->get(GiftWrappingResource::class);
        $this->getCustomerAuthenticationHeader = $objectManager->get(GetCustomerAuthenticationHeader::class);
    }

    /**
     * @magentoConfigFixture default_store sales/gift_options/allow_order 1
     * @magentoConfigFixture default_store sales/gift_options/wrapping_allow_order 1
     * @magentoConfigFixture default_store sales/gift_options/allow_gift_receipt 1
     * @magentoConfigFixture default_store sales/gift_options/allow_printed_card 1
     * @magentoApiDataFixture Magento/GiftWrapping/_files/quote/quote_with_selected_gift_wrapping.php
     *
     */
    public function testSetGiftOptionsQuery()
    {
        $quoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote_with_selected_gift_wrapping');
        $allGiftWrappings = <<<QUERY
{
   cart(cart_id: "$quoteId") {
    available_gift_wrappings  {
        id
        uid
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($allGiftWrappings);
        $this->assertArrayHasKey(2, $response['cart']['available_gift_wrappings']);
        $giftWrappingId = $response['cart']['available_gift_wrappings'][2]['id'];
        $this->assertSame(
            $response['cart']['available_gift_wrappings'][2]['uid'],
            $response['cart']['available_gift_wrappings'][2]['id']
        );

        $query = $this->getSetGiftOptionsOnCartQuery($quoteId, $giftWrappingId);
        $result = $this->graphQlMutation($query);
        $this->assertArrayHasKey('gift_wrapping', $result['setGiftOptionsOnCart']['cart']);
        $giftOptionsResponse = $result['setGiftOptionsOnCart']['cart'];
        $this->assertSame('Alex', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('Jon', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('Good job', $giftOptionsResponse['gift_message']['message']);
        $this->assertTrue($giftOptionsResponse['gift_receipt_included']);
        $this->assertTrue($giftOptionsResponse['printed_card_included']);

        $query =  <<<QUERY
mutation{
 setGiftOptionsOnCart(input: {
    cart_id: "$quoteId",
    gift_message: {
      to: ""
      from: ""
      message: ""
    },
    gift_wrapping_id:  null,
    gift_receipt_included: false
    printed_card_included: false
 })
  {
    cart {
      id
      gift_message {
        to
        from
        message
      }
      gift_wrapping {
        id
        uid
      }
      gift_receipt_included
      printed_card_included
    }
  }
}
QUERY;
        $resultWithGiftWrappingIdNull = $this->graphQlMutation($query);
        $this->assertArrayHasKey('gift_wrapping', $result['setGiftOptionsOnCart']['cart']);
        $this->assertSame(null, $resultWithGiftWrappingIdNull['setGiftOptionsOnCart']['cart']['gift_wrapping']);
        $giftOptionsResponse = $resultWithGiftWrappingIdNull['setGiftOptionsOnCart']['cart'];
        $this->assertSame('', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('', $giftOptionsResponse['gift_message']['message']);
        $this->assertFalse($giftOptionsResponse['gift_receipt_included']);
        $this->assertFalse($giftOptionsResponse['printed_card_included']);
    }

    /**
     * Tests that a guest cannot set the gift option logged-in customer's cart.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testWithGuestUserAndCustomerCart()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
          ->execute($currentEmail, $currentPassword);
        $quoteId = $this->createEmptyCart($headerAuthorization);
       
        $query = $this->getSetGiftOptionsOnCartQuery($quoteId);
        $this->expectExceptionMessage(
            "The current user cannot perform operations on cart \"$quoteId\""
        );
        
        $this->graphQlMutation($query);
    }

    /**
     * Tests that a logged-in customer cannot set gift options on a different guest cart.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testLoggedInCustomerTryingToSetGiftOptionsToOtherGuestCart()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);

        $quoteId = $this->createEmptyCart();
        $query = $this->getSetGiftOptionsOnCartQuery($quoteId);

        $this->expectExceptionMessage(
            "The current user cannot perform operations on cart \"$quoteId\""
        );
        $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );
    }

    /**
     * Tests that a logged-in customer can successfully set gift options on their own personal cart.
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store sales/gift_options/allow_order 1
     */
    public function testLoggedInCustomerTryingToSetGiftOptionsToOwnCart()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $headerAuthorization = $this->getCustomerAuthenticationHeader
            ->execute($currentEmail, $currentPassword);

        $quoteId = $this->createEmptyCart($headerAuthorization);
        $query = $this->getSetGiftOptionsOnCartQuery($quoteId);
        $giftOptionsResponse = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );
        $this->assertArrayHasKey('gift_wrapping', $giftOptionsResponse['setGiftOptionsOnCart']['cart']);
        $giftOptionsResponse = $giftOptionsResponse['setGiftOptionsOnCart']['cart'];
        $this->assertSame('Alex', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('Jon', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('Good job', $giftOptionsResponse['gift_message']['message']);
    }

    /**
     * Tests that a guest can successfully set gift options on a guest cart.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store sales/gift_options/allow_order 1
     */
    public function testGuestCanSuccessfullySetGiftOptionsOnGuestCart()
    {
        $quoteId = $this->createEmptyCart(/* without headers; i.e. guest */);
        $query = $this->getSetGiftOptionsOnCartQuery($quoteId);
        $giftOptionsResponse = $this->graphQlMutation($query);

        $this->assertArrayHasKey('gift_wrapping', $giftOptionsResponse['setGiftOptionsOnCart']['cart']);
        $giftOptionsResponse = $giftOptionsResponse['setGiftOptionsOnCart']['cart'];
        $this->assertSame('Alex', $giftOptionsResponse['gift_message']['to']);
        $this->assertSame('Jon', $giftOptionsResponse['gift_message']['from']);
        $this->assertSame('Good job', $giftOptionsResponse['gift_message']['message']);
    }

    /**
     * Create empty cart
     *
     * @param array $headerAuthorization
     * @return string
     * @throws \Exception
     */
    private function createEmptyCart(array $headerAuthorization = []): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerAuthorization
        );

        $this->cartId = $response['createEmptyCart'];

        return $this->cartId;
    }

    /**
     * Create the setGiftOptionsOnCar query
     *
     * @param string $quoteId
     * @param string|null $giftWrappingId
     * @return string
     */
    private function getSetGiftOptionsOnCartQuery(string $quoteId, string|null $giftWrappingId = null): string
    {
        $giftWrappingId = $giftWrappingId ? '"'.$giftWrappingId.'"' : 'null';
        return  <<<QUERY
          mutation{
            setGiftOptionsOnCart(input: {
              cart_id: "$quoteId",
              gift_message: {
                to: "Alex"
                from: "Jon"
                message: "Good job"
              },
              gift_wrapping_id: $giftWrappingId,
              gift_receipt_included:  true
              printed_card_included: true
          })
            {
              cart {
                id
                gift_message {
                  to
                  from
                  message
                }
                gift_wrapping {
                  id
                  uid
                }
                gift_receipt_included
                printed_card_included
              }
            }
          }
          QUERY;
    }
}
