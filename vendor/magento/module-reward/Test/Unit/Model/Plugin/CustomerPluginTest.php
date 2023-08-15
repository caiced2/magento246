<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Plugin\CustomerPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerPluginTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $rewardHelperMock;

    /**
     * @var CustomerPlugin
     */
    private $model;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(Http::class);
        $this->rewardHelperMock = $this->createMock(Data::class);

        $this->model = new CustomerPlugin(
            $this->requestMock,
            $this->rewardHelperMock
        );
    }

    public function testBeforeBeforeSaveDoesNotModifyCustomerWhenRequestIsSentFromInvalidController()
    {
        $customerMock = $this->createMock(Customer::class);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('not_backend_controller');
        $this->rewardHelperMock->expects($this->any())->method('isEnabled')->willReturn(true);

        $customerMock->expects($this->never())->method('setData');

        $this->model->beforeBeforeSave($customerMock);
    }

    public function testBeforeBeforeSaveDoesNotModifyCustomerWhenRewardFunctionalityIsDisabled()
    {
        $customerMock = $this->createMock(Customer::class);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_index_save');
        $this->rewardHelperMock->expects($this->any())->method('isEnabled')->willReturn(false);

        $customerMock->expects($this->never())->method('setData');

        $this->model->beforeBeforeSave($customerMock);
    }

    public function testBeforeBeforeSaveSetsRewardRelatedAttributesFromRequestForExistingCustomer()
    {
        $requestRewardData = [
            'reward_update_notification' => 1,
            'reward_warning_notification' => 0,
        ];

        $customerId = 1;
        $customerMock = $this->createMock(Customer::class);
        $customerMock->expects($this->any())->method('getId')->willReturn($customerId);

        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_index_save');
        $this->requestMock->expects($this->any())->method('getPost')->with('reward')->willReturn($requestRewardData);
        $this->rewardHelperMock->expects($this->any())->method('isEnabled')->willReturn(true);

        $customerMock->expects($this->exactly(2))->method('setData')->withConsecutive(
            ['reward_update_notification', 1],
            ['reward_warning_notification', 0]
        );

        $this->model->beforeBeforeSave($customerMock);
    }

    public function testBeforeBeforeSaveSetsRewardRelatedAttributesUsingConfigurationSettingsForNewCustomer()
    {
        $requestRewardData = [
            'reward_update_notification' => 0,
            'reward_warning_notification' => 0,
        ];

        $customerMock = $this->createMock(Customer::class);

        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_index_save');
        $this->requestMock->expects($this->any())->method('getPost')->with('reward')->willReturn($requestRewardData);
        $this->rewardHelperMock->expects($this->any())->method('isEnabled')->willReturn(true);

        $this->rewardHelperMock->expects($this->any())
            ->method('getNotificationConfig')
            ->with('subscribe_by_default', 0)
            ->willReturn(1);

        $customerMock->expects($this->exactly(2))->method('setData')->withConsecutive(
            ['reward_update_notification', 1],
            ['reward_warning_notification', 1]
        );

        $this->model->beforeBeforeSave($customerMock);
    }
}
