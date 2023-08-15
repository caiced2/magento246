<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Unit\Model\Bolt\Auth;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\QuickCheckout\Model\Bolt\Auth\JwtManagerInterface;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthException;
use Magento\QuickCheckout\Model\Bolt\Auth\IdTokenDecoder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IdTokenDecoderTest extends TestCase
{
    private const ACCESS_TOKEN = 'some_access_token';
    private const TEST_EMAIL = 'test@adobe.com';
    private const IS_EMAIL_VERIFIED = 1;

    /**
     * @var IdTokenDecoder
     */
    private $idTokenDecoder;

    /**
     * @var ClientInterface|MockObject
     */
    private $serviceClient;

    /**
     * @var TransferFactoryInterface|MockObject
     */
    private $transferFactory;

    /**
     * @var JwtManagerInterface|MockObject
     */
    private $jwtManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->serviceClient = $this->createMock(ClientInterface::class);
        $this->transferFactory = $this->createMock(TransferFactoryInterface::class);
        $this->jwtManager = $this->createMock(JwtManagerInterface::class);

        $this->idTokenDecoder = new IdTokenDecoder(
            $this->transferFactory,
            $this->serviceClient,
            $this->jwtManager
        );

        $this->transferFactory->method('create')
            ->willReturn($this->createMock(TransferInterface::class));
    }

    /**
     * @dataProvider invalidConfigIdResponseProvider
     */
    public function testFailsWithAnInvalidConfigIdResponse(array $response)
    {
        $this->serviceClient->expects($this->exactly(1))
            ->method('placeRequest')
            ->willReturn($response);

        $this->expectException(OauthException::class);
        $this->expectExceptionMessage('Invalid open id config: invalid jwks uri');

        $this->idTokenDecoder->decode(self::ACCESS_TOKEN);
    }

    /**
     * @dataProvider invalidAccessTokenPayloadProvider
     */
    public function testFailsWithAnInvalidAccessToken(array $payload)
    {
        $openIdConfig = ['jwks_uri' => 'http://example.com/some/path/to/jwks.json'];

        $jwks = [];

        $this->serviceClient->method('placeRequest')
            ->willReturnOnConsecutiveCalls($openIdConfig, $jwks);

        $this->jwtManager->expects($this->once())
            ->method('decode')
            ->with(self::ACCESS_TOKEN, $jwks)
            ->willReturn($payload);

        $this->expectException(OauthException::class);
        $this->expectExceptionMessage('Invalid access token payload: missing email or email verification');

        $this->idTokenDecoder->decode(self::ACCESS_TOKEN);
    }

    public function testReturnsTheAccessTokenPayloadSuccessfully()
    {
        $openIdConfig = ['jwks_uri' => 'http://example.com/some/path/to/jwks.json'];

        $jwks = ['keys' => []];

        $payload = [
            'email' => self::TEST_EMAIL,
            'email_verified' => self::IS_EMAIL_VERIFIED
        ];

        $this->serviceClient->method('placeRequest')
            ->willReturnOnConsecutiveCalls($openIdConfig, $jwks);

        $this->jwtManager->expects($this->once())
            ->method('decode')
            ->with(self::ACCESS_TOKEN, $jwks)
            ->willReturn($payload);

        $result = $this->idTokenDecoder->decode(self::ACCESS_TOKEN);
        $this->assertEquals(self::TEST_EMAIL, $result->getEmail());
        $this->assertEquals(true, $result->isEmailVerified());
    }

    /**
     * @return array
     */
    public function invalidConfigIdResponseProvider(): array
    {
        return [
            [[]],
            [['some_config' => 'some_config_value']],
            [['jwks_uri' => 'invalid_jwks_uri']],
            [['jwks_uri' => 'http://example.com']],
        ];
    }

    /**
     * @return array
     */
    public function invalidAccessTokenPayloadProvider(): array
    {
        return [
            [[]],
            [['email' => '', 'email_verified' => 0]],
            [['email_verified' => 0]],
            [['email' => self::TEST_EMAIL,]],
        ];
    }
}
