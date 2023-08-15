<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Block\Adminhtml\ConfigurableProduct\Edit\Tab\Variations\Plugin;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PricePermissions\Block\Adminhtml\ConfigurableProduct\Product\Edit\Tab\Variations\Plugin\Config;
use Magento\PricePermissions\Helper\Data;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Session|MockObject
     */
    protected $authSession;

    /**
     * @var User|MockObject
     */
    protected $user;

    /**
     * @var Data|MockObject
     */
    protected $pricePermData;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->authSession = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser'])
            ->onlyMethods(['isLoggedIn'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pricePermData = $this->createMock(Data::class);

        $this->user = $this->createMock(User::class);

        $this->config = $this->objectManager->getObject(
            Config::class,
            [
                'authSession' => $this->authSession,
                'pricePermData' => $this->pricePermData,
            ]
        );
    }

    public function testBeforeToHtmlWithPermissions()
    {
        $subject = $this->getMockBuilder(
            \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config::class
        )->addMethods(['setCanEditPrice', 'setCanReadPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->expects($this->any())->method('getRole')->willReturn('admin');
        $this->authSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->authSession->expects($this->any())->method('getUser')->willReturn($this->user);
        $this->pricePermData->expects($this->once())->method('getCanAdminReadProductPrice')->willReturn(true);
        $this->pricePermData->expects($this->once())->method('getCanAdminEditProductPrice')->willReturn(true);
        $subject->expects($this->never())->method('setCanEditPrice');
        $subject->expects($this->never())->method('setCanReadPrice');

        $this->config->beforeToHtml($subject);
    }

    public function testBeforeToHtmlWithoutPermissions()
    {
        $subject = $this->getMockBuilder(
            \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config::class
        )->addMethods(['setCanEditPrice', 'setCanReadPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->expects($this->any())->method('getRole')->willReturn('admin');
        $this->authSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->authSession->expects($this->any())->method('getUser')->willReturn($this->user);
        $this->pricePermData->expects($this->once())->method('getCanAdminReadProductPrice')->willReturn(false);
        $this->pricePermData->expects($this->once())->method('getCanAdminEditProductPrice')->willReturn(false);
        $subject->expects($this->once())->method('setCanEditPrice')->with(false);
        $subject->expects($this->once())->method('setCanReadPrice')->with(false);

        $this->config->beforeToHtml($subject);
    }
}
