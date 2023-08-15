<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Rma\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $rmaConfig;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfig;

    /**
     * @var Store|MockObject
     */
    protected $store;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->store = $this->createMock(Store::class);

        $this->rmaConfig = new Config($this->scopeConfig, $this->storeManager);
    }

    /**
     * @return void
     */
    public function testSetStore(): void
    {
        $storeId = 5;
        $this->rmaConfig->setStore($this->store);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnMap(
                [
                    [$storeId, $this->store],
                    [null, $this->store]
                ]
            );
        $this->rmaConfig->setStore($storeId);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->rmaConfig->setStore(null);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
    }

    /**
     * @return void
     */
    public function testGetStore(): void
    {
        $storeId = 5;
        $this->rmaConfig->setStore($this->store);
        $this->assertEquals($this->rmaConfig->getStore($this->store), $this->store);
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturnMap(
                [
                    [$storeId, $this->store],
                    [null, $this->store]
                ]
            );
        $this->rmaConfig->setStore($storeId);
        $this->assertEquals($this->store, $this->rmaConfig->getStore($storeId));
        $this->rmaConfig->setStore(null);
        $this->assertEquals($this->store, $this->rmaConfig->getStore());
    }

    /**
     * @return void
     */
    public function testSetGetRootPath(): void
    {
        $path = 'path';
        $this->rmaConfig->setRootPath($path);
        $this->assertEquals($path, $this->rmaConfig->getRootPath(''));
    }

    /**
     * @return void
     */
    public function testGetRootRmaEmail(): void
    {
        $this->assertEquals(Config::XML_PATH_RMA_EMAIL, $this->rmaConfig->getRootRmaEmail());
    }

    /**
     * @return void
     */
    public function testGetRootAuthEmail(): void
    {
        $this->assertEquals(Config::XML_PATH_AUTH_EMAIL, $this->rmaConfig->getRootAuthEmail());
    }

    /**
     * @return void
     */
    public function testGetRootCommentEmail(): void
    {
        $this->assertEquals(Config::XML_PATH_COMMENT_EMAIL, $this->rmaConfig->getRootCommentEmail());
    }

    /**
     * @return void
     */
    public function testGetRootCustomerCommentEmail(): void
    {
        $this->assertEquals(
            Config::XML_PATH_CUSTOMER_COMMENT_EMAIL,
            $this->rmaConfig->getRootCustomerCommentEmail()
        );
    }

    /**
     * @return void
     */
    public function testIsEnabled(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_ENABLED,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);
        $this->assertTrue($this->rmaConfig->isEnabled());
    }

    /**
     * @return void
     */
    public function testGetCopyTo(): void
    {
        $data = 'copy1,copy2';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_COPY_TO,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($data);
        $this->assertEquals(explode(',', $data), $this->rmaConfig->getCopyTo());
    }

    /**
     * @return void
     */
    public function testGetCopyToFalse(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_COPY_TO,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(false);
        $this->assertFalse($this->rmaConfig->getCopyTo());
    }

    /**
     * @return void
     */
    public function testGetCopyMethod(): void
    {
        $data = 'bcc';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_COPY_METHOD,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($data);
        $this->assertEquals($data, $this->rmaConfig->getCopyMethod());
    }

    /**
     * @return void
     */
    public function testGetGuestTemplate(): void
    {
        $data = 'guest tmpl';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_GUEST_TEMPLATE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($data);
        $this->assertEquals($data, $this->rmaConfig->getGuestTemplate());
    }

    /**
     * @return void
     */
    public function testGetTemplate(): void
    {
        $data = 'tmpl';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($data);
        $this->assertEquals($data, $this->rmaConfig->getTemplate());
    }

    /**
     * @return void
     */
    public function testGetIdentity()
    {
        $data = 'rma';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($data);
        $this->assertEquals($data, $this->rmaConfig->getIdentity('', null));
    }

    /**
     * @return void
     */
    public function testGetCustomerEmailRecipient(): void
    {
        $senderCode = 'rma';
        $data = 'emailRecipient';
        $this->scopeConfig
            ->method('getValue')
            ->withConsecutive(
                [
                    Config::XML_PATH_CUSTOMER_COMMENT_EMAIL_RECIPIENT,
                    ScopeInterface::SCOPE_STORE,
                    null
                ],
                [
                    'trans_email/ident_' . $senderCode . '/email',
                    ScopeInterface::SCOPE_STORE,
                    null
                ]
            )
            ->willReturnOnConsecutiveCalls($senderCode, $data);
        $this->assertEquals($data, $this->rmaConfig->getCustomerEmailRecipient(null));
    }
}
