<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Controller;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test allowed endpoints when website restriction is enabled
 */
class AllowedActionTest extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var int
     */
    private $currentStore;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = $this->_objectManager->get(OrderRepository::class);
        $this->urlEncoder = $this->_objectManager->get(EncoderInterface::class);
        $this->currentStore = $this->_objectManager->get(StoreManagerInterface::class)->getStore()->getId();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->_objectManager->get(StoreManagerInterface::class)->setCurrentStore($this->currentStore);
        parent::tearDown();
    }

    /**
     * @magentoConfigFixture current_store general/restriction/is_active 1
     * @magentoConfigFixture current_store general/restriction/mode 1
     * @magentoConfigFixture current_store general/restriction/http_status 1
     * @magentoDataFixture Magento/Shipping/_files/track.php
     */
    public function testShippingTrackingPopupAction(): void
    {
        $order = $this->getOrder('100000001');
        $popupUrl = $this->getPopupUrl($order);

        $this->dispatch($popupUrl);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('track_number', $this->getResponse()->getBody());
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture current_store general/restriction/is_active 1
     * @magentoConfigFixture current_store general/restriction/mode 1
     * @magentoConfigFixture current_store general/restriction/http_redirect 1
     * @magentoConfigFixture current_store general/restriction/cms_page home
     * @magentoConfigFixture current_store general/restriction/http_status 0
     * @magentoConfigFixture current_store web/url/use_store 1
     * @magentoConfigFixture fixture_second_store_store web/url/use_store 1
     */
    public function testStoreRedirectAction(): void
    {
        $this->getRequest()->setParam('___store', 'fixture_second_store');
        $this->getRequest()->setParam('___from_store', 'default');
        $this->dispatch('stores/store/redirect');
        $header = $this->getResponse()->getHeader('Location');
        $this->assertNotEmpty($header);
        $result = $header->getFieldValue();
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlParts = parse_url($result);
        $this->assertStringEndsWith('fixture_second_store/stores/store/switch/', $urlParts['path']);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture fixture_second_store_store general/restriction/is_active 1
     * @magentoConfigFixture fixture_second_store_store general/restriction/mode 1
     * @magentoConfigFixture fixture_second_store_store general/restriction/http_redirect 1
     * @magentoConfigFixture fixture_second_store_store general/restriction/cms_page home
     * @magentoConfigFixture fixture_second_store_store general/restriction/http_status 0
     * @magentoConfigFixture fixture_second_store_store web/url/use_store 1
     * @magentoConfigFixture fixture_second_store_store web/url/use_store 1
     */
    public function testStoreSwitchAction(): void
    {
        $storeManager = $this->_objectManager->get(StoreManagerInterface::class);
        $urlHelper = $this->_objectManager->get(\Magento\Framework\Url\Helper\Data::class);
        $storeManager->setCurrentStore('fixture_second_store');
        $url = $storeManager->getStore()->getUrl('customer/account/login');
        $this->getRequest()->setParam(ActionInterface::PARAM_NAME_URL_ENCODED, $urlHelper->getEncodedUrl($url));
        $this->getRequest()->setParam('___store', 'fixture_second_store');
        $this->getRequest()->setParam('___from_store', 'default');
        $this->dispatch('stores/store/switch');
        $header = $this->getResponse()->getHeader('Location');
        $this->assertNotEmpty($header);
        $result = $header->getFieldValue();
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlParts = parse_url($result);
        $this->assertStringEndsWith('fixture_second_store/customer/account/login/', $urlParts['path']);
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function getPopupUrl(OrderInterface $order): string
    {
        $hash = "order_id:{$order->getEntityId()}:{$order->getProtectCode()}";
        return 'shipping/tracking/popup?hash=' . $this->urlEncoder->encode($hash);
    }

    /**
     * @param string $incrementalId
     * @return OrderInterface|null
     */
    private function getOrder(string $incrementalId): ?OrderInterface
    {
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->_objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter(OrderInterface::INCREMENT_ID, $incrementalId)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        /** @var OrderInterface $order */
        $order = reset($orders);

        return $order;
    }
}
