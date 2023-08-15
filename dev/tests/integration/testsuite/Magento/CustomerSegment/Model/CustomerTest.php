<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\CustomerSegment\Model\Customer as CustomerSegment;
use Magento\CustomerSegment\Test\Fixture\Segment as SegmentFixture;
use Magento\CustomerSegment\Test\Fixture\SegmentWithComplexConditions;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Website;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PreconditionFailedException;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use PHPUnit\Framework\TestCase;

/**
 * Test matched customers segment with "Real-time Check if Customer is Matched by Segment" = NO
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTest extends TestCase
{
    /** @var PublisherConsumerController */
    private $publisherConsumerController;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var string[] */
    private $consumers = ['matchCustomerSegmentProcessor'];

    /** @var MutableScopeConfigInterface */
    private $config;

    /** @var \Magento\TestFramework\Fixture\DataFixtureStorage */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->publisherConsumerController = Bootstrap::getObjectManager()->create(
            PublisherConsumerController::class,
            [
                'consumers' => $this->consumers,
                'logFilePath' => TESTS_TEMP_DIR . "/MessageQueueTestLog.txt",
                'maxMessages' => null,
                'appInitParams' => Bootstrap::getInstance()->getAppInitParams()
            ]
        );
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->config = $this->objectManager->get(MutableScopeConfigInterface::class);
        try {
            $this->publisherConsumerController->startConsumers();
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (PreconditionFailedException $e) {
            $this->fail(
                $e->getMessage()
            );
        }
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->publisherConsumerController->stopConsumers();
        parent::tearDown();
        $this->config->setValue(
            'customer/magento_customersegment/real_time_check_if_customer_is_matched_by_segment',
            1
        );
    }

    /**
     * Match customers segment by country, city, gender, group.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws PreconditionFailedException
     * @magentoConfigFixture customer/magento_customersegment/real_time_check_if_customer_is_matched_by_segment 0
     * @magentoDataFixture Magento/Customer/_files/three_customers.php
     * @magentoDataFixture Magento/Customer/_files/customer_with_uk_address.php
     * @magentoDataFixture Magento/Customer/_files/new_customer.php
     * @magentoDataFixture Magento/Customer/_files/two_customers_with_different_customer_groups.php
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     */
    #[
        DataFixture(SegmentWithComplexConditions::class, [
            'conditions' => [
                '1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class,
                    'aggregator' => 'any',
                    'value' => '1',
                    'new_child' => ''
                ],
                '1--1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address::class,
                    'aggregator' => 'all',
                    'new_child' => ''
                ],
                '1--1--1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes::class,
                    'attribute' => 'country_id',
                    'operator' => '==',
                    'value' => 'GB'
                ],
                '1--1--2' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes::class,
                    'attribute' => 'city',
                    'operator' => '==',
                    'value' => 'London'
                ],
                '1--2' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class,
                    'attribute' => 'gender',
                    'operator' => '==',
                    'value' => '1'
                ],
                '1--3' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class,
                    'attribute' => 'group_id',
                    'operator' => '==',
                    'value' => '2'
                ]
            ]
        ], 'segment_with_condition'),
        DataFixture(SegmentFixture::class, as: 'segment_without_condition')
    ]
    public function testProcessEventCustomerAddressAndAttribute():void
    {
        $segmentWithConditionId = $this->fixtures->get('segment_with_condition')->getId();
        $segmentWithoutConditionId = $this->fixtures->get('segment_without_condition')->getId();
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get('customer_uk_address@test.com');
        // Assert that customer from UK AND London is matched by correct segments
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithConditionId, $segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
        // Assert that Male customer is matched by correct segments
        $customer = $this->customerRepository->get('new_customer@example.com');
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithConditionId, $segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
        // Assert that Wholesale group customer is matched by correct segments
        $customer = $this->customerRepository->get('customer_two@example.com');
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithConditionId, $segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
        // Assert that other customers are matched only by segment without condition
        $notMatchedCustomers = [
            'customer@example.com',
            'customer2@search.example.com',
            'customer3@search.example.com',
            'customer_with_addresses@test.com'
        ];
        foreach ($notMatchedCustomers as $notMatchedCustomer) {
            $customer = $this->customerRepository->get($notMatchedCustomer);
            $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
            $this->assertEquals(
                [$segmentWithoutConditionId],
                $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
            );
        }
    }

    /**
     * Match customers segment with multiple websites by state and default billing address.
     *
     * @return void
     * @throws PreconditionFailedException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @magentoConfigFixture customer/magento_customersegment/real_time_check_if_customer_is_matched_by_segment 0
     * @magentoDataFixture Magento/Customer/_files/customer_with_group_and_address.php
     */
    #[
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'store_group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$store_group2.id$'], 'store2'),
        DataFixture(
            Customer::class,
            [
                'email' => 'customer_2@example.com',
                'password' => 'password',
                'store_id' => '$store2.id$',
                'website_id' => '$website2.id$',
                'addresses' => [
                    [
                        'country_id' => 'US',
                        'region_id' => 1,
                        'city' => 'Mobile',
                        'street' => ['1059 George Avenue'],
                        'postcode' => '36608',
                        'telephone' => '251-366-0271',
                        'default_billing' => true,
                        'default_shipping' => true
                    ]
                ]
            ],
            as: 'customer2'
        ),
        DataFixture(SegmentWithComplexConditions::class, [
            'website_ids' => [1, '$website2.id$'],
            'conditions' => [
                '1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class,
                    'aggregator' => 'all',
                    'value' => '1',
                    'new_child' => ''
                ],
                '1--1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address::class,
                    'aggregator' => 'all',
                    'new_child' => ''
                ],
                '1--1--1' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Attributes::class,
                    'attribute' => 'region_id',
                    'operator' => '==',
                    'value' => '1'
                ],
                '1--1--2' => [
                    'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\DefaultAddress::class,
                    'operator' => '==',
                    'value' => 'default_billing'
                ]
            ]
        ], 'segment_with_condition')
    ]
    public function testProcessEventMultipleWebsite():void
    {
        $segmentId = $this->fixtures->get('segment_with_condition')->getId();
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get('customer@example.com');
        $mainWebsite = $this->objectManager->create(Website::class)->load('base');
        $secondWebsite = $this->fixtures->get('website2');
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        // Assert that customer is matched by segment with customer's website
        $customerSegment->processEvent('customer_login', $customer->getId(), $mainWebsite->getWebsiteId());
        $this->assertEquals(
            [$segmentId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $mainWebsite->getWebsiteId())
        );
        // Assert that customer is NOT matched by segment because of incorrect website
        $customerSegment->processEvent('customer_login', $customer->getId(), $secondWebsite->getWebsiteId());
        $this->assertEmpty(
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $secondWebsite->getWebsiteId())
        );
        // Assert that second customer is matched by segment with customer's website
        $secondCustomer = $this->customerRepository->get('customer_2@example.com', $secondWebsite->getWebsiteId());
        $customerSegment->processEvent('customer_login', $secondCustomer->getId(), $secondWebsite->getWebsiteId());
        $this->assertEquals(
            [$segmentId],
            $customerSegment->getCustomerSegmentIdsForWebsite($secondCustomer->getId(), $secondCustomer->getWebsiteId())
        );
        // Assert that second customer is NOT matched by segment because of incorrect website
        $customerSegment->processEvent('customer_login', $secondCustomer->getId(), $mainWebsite->getWebsiteId());
        $this->assertEmpty(
            $customerSegment->getCustomerSegmentIdsForWebsite($secondCustomer->getId(), $mainWebsite->getWebsiteId())
        );
    }

    /**
     * Match customers segment by newsletter subscription with a specific date range.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @magentoConfigFixture customer/magento_customersegment/real_time_check_if_customer_is_matched_by_segment 0
     * @magentoDataFixture Magento/Newsletter/_files/three_subscribers.php
     */
    #[
        DataFixture(SegmentWithComplexConditions::class, ['conditions' => [
            '1' => [
                'type' => \Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class,
                'aggregator' => 'all',
                'value' => '1',
                'new_child' => ''
            ],
            '1--1' => [
                'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Newsletter::class,
                'value' => '1',
                'new_child' => ''
            ],
            '1--2' => [
                'type' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class,
                'attribute' => 'created_at',
                'operator' => '<=',
                'value' => '2014-01-01'
            ]
        ]], 'segment_with_condition'),
        DataFixture(SegmentFixture::class, as: 'segment_without_condition')
    ]
    public function testProcessEventCustomerWithSubscription():void
    {
        $segmentWithConditionId = $this->fixtures->get('segment_with_condition')->getId();
        $segmentWithoutConditionId = $this->fixtures->get('segment_without_condition')->getId();
        $customerSegment = $this->objectManager->create(CustomerSegment::class);
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get('customer@search.example.com');
        // Assert that customer with subscription but out of date range is matched only by segment without conditions
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
        // Assert that customer with subscription and within a date range is matched by correct segments
        $customer = $this->customerRepository->get('customer2@search.example.com');
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithConditionId, $segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
        // Assert that customer with subscription and within a date range is matched by correct segments
        $customer = $this->customerRepository->get('customer3@search.example.com');
        $customerSegment->processEvent('customer_login', $customer->getId(), $customer->getWebsiteId());
        $this->assertEquals(
            [$segmentWithConditionId, $segmentWithoutConditionId],
            $customerSegment->getCustomerSegmentIdsForWebsite($customer->getId(), $customer->getWebsiteId())
        );
    }
}
