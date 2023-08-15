<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Unit\Model\Bolt\Auth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthException;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenResolver;
use Magento\QuickCheckout\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OauthTokenResolverTest extends TestCase
{
    private const ACCESS_TOKEN = 'some_access_token';
    private const ACCESS_TOKEN_SCOPE = 'bolt.account.view';
    private const REFRESH_TOKEN = 'refresh_token';
    private const REFRESH_TOKEN_SCOPE = 'scope.token';
    private const ID_TOKEN = 'some_id_token';

    private const ACCESS_CODE = 'access_code';

    /**
     * @var OauthTokenResolver
     */
    private $oauthTokenResolver;

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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->serviceClient = $this->createMock(ClientInterface::class);
        $this->transferFactory = $this->createMock(TransferFactoryInterface::class);

        $this->oauthTokenResolver = new OauthTokenResolver(
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
        $this->oauthTokenResolver->exchange(self::ACCESS_CODE);
    }

    public function testOauthTokenRetrievedSuccessfully()
    {
        $response = [
            'access_token' => self::ACCESS_TOKEN,
            'scope' => self::ACCESS_TOKEN_SCOPE,
            'expires_in' => 3600,
            'refresh_token' => self::REFRESH_TOKEN,
            'refresh_token_scope' => self::REFRESH_TOKEN_SCOPE,
            'id_token' => self::ID_TOKEN,
        ];

        $this->givenTheOauthResponse($response);
        $token = $this->oauthTokenResolver->exchange(self::ACCESS_CODE);
        self::assertEquals(self::ACCESS_TOKEN, $token->getAccessToken());
        self::assertEquals(self::ACCESS_TOKEN_SCOPE, $token->getAccessTokenScope());
        self::assertEquals(self::REFRESH_TOKEN, $token->getRefreshToken());
        self::assertEquals(self::REFRESH_TOKEN_SCOPE, $token->getRefreshTokenScope());
        self::assertEquals(self::ID_TOKEN, $token->getIdToken());
    }

    /**
     * @return array
     */
    public function invalidOauthResponseProvider(): array
    {
        return [
            [['access_token' => '', 'id_token' => self::ID_TOKEN]],
            [['id_token' => self::ID_TOKEN]],
            [['access_token' => self::ACCESS_TOKEN, 'id_token' => '']],
            [['access_token' => self::ACCESS_TOKEN]],
            [['access_token' => self::ACCESS_TOKEN, 'id_token' => self::ID_TOKEN]],
            [['access_token' => self::ACCESS_TOKEN, 'id_token' => self::ID_TOKEN, 'refresh_token' => '']],
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
