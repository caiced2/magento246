<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Plugin\Framework\Stdlib\Cookie;

use Closure;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\GoogleTagManager\Plugin\Framework\Stdlib\Cookie\PhpCookieManagerPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PhpCookieManagerPlugin
 */
class PhpCookieManagerPluginTest extends TestCase
{
    /**
     * @var SessionManagerInterface|MockObject
     */
    private $sessionManagerMock;

    /**
     * @var PhpCookieManager|MockObject
     */
    private $phpCookieManagerMock;

    /**
     * @var Closure
     */
    private $closureMock;

    /**
     * @var PublicCookieMetadata|MockObject
     */
    private $publicCookieMetadata;

    /**
     * @var PhpCookieManagerPlugin
     */
    private $phpCookieManagerPlugin;

    public function setUp(): void
    {
        $this->sessionManagerMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setAddToCartAdvanced'])
            ->getMockForAbstractClass();

        $subject = $this->phpCookieManagerMock = $this->createMock(PhpCookieManager::class);

        $this->publicCookieMetadata = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setDuration', 'setPath', 'setHttpOnly', 'setSameSite'])
            ->getMock();

        $this->publicCookieMetadata->expects($this->any())
            ->method('setDuration')
            ->willReturnSelf();
        $this->publicCookieMetadata->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();
        $this->publicCookieMetadata->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();
        $this->publicCookieMetadata->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->closureMock = function () use ($subject) {
            return $subject;
        };

        $this->phpCookieManagerPlugin = new PhpCookieManagerPlugin($this->sessionManagerMock);
    }

    /**
     * Test for PhpCookieManagerPlugin::aroundSetPublicCookie
     *
     * @param string $cookieName
     * @param string $cookieContent
     * @return void
     *
     * @dataProvider aroundSetPublicCookieDataProvider
     */
    public function testAroundSetPublicCookie(string $cookieName, string $cookieContent): void
    {
        $args = [$cookieName, $cookieContent, $this->publicCookieMetadata];

        if ($cookieContent > PhpCookieManager::MAX_COOKIE_SIZE) {
            $this->sessionManagerMock->expects($this->once())
                ->method('setAddToCartAdvanced')
                ->with([$cookieContent])
                ->willReturnSelf();
        }

        $this->assertNull(
            $this->phpCookieManagerPlugin->aroundSetPublicCookie(
                $this->phpCookieManagerMock,
                $this->closureMock,
                ...$args
            )
        );
    }

    /**
     * Data provider for PhpCookieManagerPlugin::aroundSetPublicCookie
     *
     * @return array[]
     */
    public function aroundSetPublicCookieDataProvider(): array
    {
        return [
            'other_cookies' => [
                'cookie_name' => 'login_redirect',
                'cookie_content' => json_encode('')
            ],
            'cookie_size_under_4096' => [
                'cookie_name' => 'add_to_cart',
                'cookie_content' => json_encode(
                    $this->randString(
                        rand(1, 4095 - strlen('add_to_cart') - 1)
                    )
                )
            ],
            'cookie_size_over_4096' => [
                'cookie_name' => 'add_to_cart',
                'cookie_content' => json_encode(
                    $this->randString(
                        rand(4096, 15600 - strlen('add_to_cart') - 1)
                    )
                )
            ]
        ];
    }

    /**
     * Used for random string generation of needed length
     *
     * @param int $length
     * @return string
     */
    private function randString(int $length): string
    {
        $str = '';
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }

        return $str;
    }
}
