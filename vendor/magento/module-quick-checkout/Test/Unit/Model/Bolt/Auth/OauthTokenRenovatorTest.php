<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Unit\Model\Bolt\Auth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthException;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenRenovator;
use Magento\QuickCheckout\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OauthTokenRenovatorTest extends TestCase
{
    private const NEW_ACCESS_TOKEN = 'new__access_token';
    private const NEW_ACCESS_TOKEN_SCOPE = 'bolt.account.view';
    private const NEW_REFRESH_TOKEN = 'new_refresh_token';

    private const REFRESH_TOKEN = 'refresh_token';
    private const REFRESH_TOKEN_SCOPE = 'bolt.account.manage';

    /**
     * @var OauthTokenRenovator
     */
    private $oauthTokenRenovator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ClientInterface|MockObject
     */
    private $serviceClient;

    /**
     * @var TransferFactoryInterface|MockObject
     */
    private $transferFactory;

    /**
     * @var Manager|MockObject
     */
    private $moduleManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->moduleManager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = new Config(
            $this->createMock(ScopeConfigInterface::class),
            $this->moduleManager,
            $this->createMock(StoreManagerInterface::class)
        );

        $this->serviceClient = $this->createMock(ClientInterface::class);
        $this->transferFactory = $this->createMock(TransferFactoryInterface::class);

        $this->oauthTokenRenovator = new OauthTokenRenovator(
            $this->config,
            $this->transferFactory,
            $this->serviceClient,
        );

        $this->transferFactory->method('create')
            ->willReturn($this->createMock(TransferInterface::class));
    }

    /**
     * @dataProvider invalidOauthResponseProvider
     */
    public function testFailsWithInvalidOauthResponse(array $response)
    {
        $this->givenTheOauthResponse($response);
        $this->thenItShouldThrowAnError();
        $this->oauthTokenRenovator->refresh(self::REFRESH_TOKEN, self::REFRESH_TOKEN_SCOPE);
    }

    public function testOauthTokenRetrievedSuccessfully()
    {
        $response = [
            'access_token' => self::NEW_ACCESS_TOKEN,
            'scope' => self::NEW_ACCESS_TOKEN_SCOPE,
            'expires_in' => 3600,
            'refresh_token' => self::NEW_REFRESH_TOKEN,
            'refresh_token_scope' => self::REFRESH_TOKEN_SCOPE,
        ];

        $this->givenTheOauthResponse($response);
        $token = $this->oauthTokenRenovator->refresh(self::REFRESH_TOKEN, self::REFRESH_TOKEN_SCOPE);
        self::assertEquals(self::NEW_ACCESS_TOKEN, $token->getAccessToken());
        self::assertEquals(self::NEW_ACCESS_TOKEN_SCOPE, $token->getAccessTokenScope());
        self::assertEquals(self::NEW_REFRESH_TOKEN, $token->getRefreshToken());
        self::assertEquals(self::REFRESH_TOKEN_SCOPE, $token->getRefreshTokenScope());
    }

    /**
     * @return array
     */
    public function invalidOauthResponseProvider(): array
    {
        return [
            [['access_token' => self::NEW_ACCESS_TOKEN, 'refresh_token' => '']],
            [['access_token' => self::NEW_ACCESS_TOKEN]],
            [['access_token' => self::NEW_ACCESS_TOKEN, 'refresh_token' => self::REFRESH_TOKEN_SCOPE]],
            [
                [
                    'access_token' => self::NEW_ACCESS_TOKEN,
                    'refresh_token' => self::REFRESH_TOKEN_SCOPE,
                    'refresh_token_scope' => ''
                ]
            ],
        ];
    }

    /**
     * @return void
     */
    public function thenItShouldThrowAnError(): void
    {
        $this->expectException(OauthException::class);
        $this->expectExceptionMessage('Invalid Oauth response');
    }

    /**
     * @param array $response
     * @return void
     */
    private function givenTheOauthResponse(array $response): void
    {
        $this->serviceClient->method('placeRequest')
            ->willReturn($response);
    }
}
