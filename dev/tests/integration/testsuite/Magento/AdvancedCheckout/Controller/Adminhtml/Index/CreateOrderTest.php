<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\MessageInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * 'Create Order' Controller integration tests.
 *
 * @magentoAppArea adminhtml
 */
class CreateOrderTest extends AbstractBackendController
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
    }

    /**
     * Test that items of active Quote are deleted after creating new Quote.
     *
     * @return void
     * @magentoDataFixture Magento/Sales/_files/quote_with_two_products_and_customer.php
     */
    public function testQuoteItemsAreDeletedAfterOrderIsCreated(): void
    {
        $activeQuote = $this->quoteRepository->getActiveForCustomer(1);
        $this->assertTrue($activeQuote->hasItems());
        $this->assertNotEquals(0, $activeQuote->getGrandTotal());

        $this->getRequest()->setParams([
            'customer' => 1,
            'store' => 1,
        ]);
        $this->dispatch('backend/checkout/index/createOrder');
        $activeQuote = $this->getCustomerQuote();
        $this->assertTrue($activeQuote->hasItems());
        $this->assertNotEquals(0, $activeQuote->getGrandTotal());
        $this->placeOrder();
        $activeQuote = $this->getCustomerQuote();

        $this->assertFalse($activeQuote->hasItems());
        $this->assertEquals(0, $activeQuote->getGrandTotal());

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder
            ->addFilter('customer_id', 1)
            ->addFilter('main_table.' . CartInterface::KEY_IS_ACTIVE, 0);
        $searchResult = $this->quoteRepository->getList($searchCriteriaBuilder->create());

        $this->assertEquals(1, $searchResult->getTotalCount());
        $newQuote = current($searchResult->getItems());
        $this->assertTrue($newQuote->hasItems());
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function placeOrder(): void
    {
        $this->_request = null;
        $this->_response = null;
        Bootstrap::getInstance()->getBootstrap()->getApplication()->reinitialize();
        Bootstrap::getInstance()->loadArea('adminhtml');
        $this->_objectManager = Bootstrap::getObjectManager();
        $this->getRequest()
            ->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST)
            ->setPostValue([
                'order' => [
                    'account' => [
                        'email' => 'john.doe001@test.com',
                    ],
                    'shipping_method' => 'flatrate_flatrate',
                    'payment_method' => 'checkmo',
                ],
                'collect_shipping_rates' => true
            ]);
        $this->dispatch('backend/sales/order_create/save');
        $this->assertSessionMessages(
            $this->isEmpty(),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages(
            $this->equalTo([(string)__('You created the order.')]),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * @return CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerQuote(): CartInterface
    {
        $this->quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        return $this->quoteRepository->getActiveForCustomer(1);
    }
}
