<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Framework\Math\Random;
use Magento\Framework\Model\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Model\Pool;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Random
     */
    private $randomMath;

    /**
     * @var WebsiteInterface
     */
    private $website;

    /**
     * @var Pool
     */
    private $model;

    private const XML_CONFIG_CODE_FORMAT = 'giftcard/giftcardaccount_general/code_format';

    private const XML_CONFIG_CODE_LENGTH = 'giftcard/giftcardaccount_general/code_length';

    private const XML_CONFIG_CODE_PREFIX = 'giftcard/giftcardaccount_general/code_prefix';

    private const XML_CONFIG_CODE_SUFFIX = 'giftcard/giftcardaccount_general/code_suffix';

    private const XML_CONFIG_CODE_SPLIT = 'giftcard/giftcardaccount_general/code_split';

    private const XML_CONFIG_POOL_SIZE = 'giftcard/giftcardaccount_general/pool_size';

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->randomMath = $this->createMock(Random::class);
        $this->website = $this->createPartialMock(Website::class, ['getConfig']);

        $giftCodeParams = [
            'charset' => ['alphanum' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789']
        ];

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Pool::class,
            [
                'context' => $this->context,
                'storeManager' => $this->storeManager,
                'randomMath' => $this->randomMath,
                'giftCardCodeParams' => $giftCodeParams,
            ]
        );
    }

    /**
     * @param array $map
     * @param string $expectedCode
     * @dataProvider generateCodeProvider
     * @throws \ReflectionException
     */
    public function testGenerateCode(array $map, $expectedCode)
    {
        $this->storeManager->method('getWebsite')
            ->willReturn($this->website);
        $this->randomMath->method('getRandomString')
            ->willReturn('TEST123');
        $this->website->method('getConfig')
            ->willReturnMap($map);

        $method = new \ReflectionMethod($this->model, '_generateCode');
        $method->setAccessible(true);

        $this->assertEquals($expectedCode, $method->invoke($this->model));
    }

    /**
     * @return array
     */
    public function generateCodeProvider(): array
    {
        $mapNoPrefixOrSuffix = [
            [self::XML_CONFIG_POOL_SIZE, 50],
            [self::XML_CONFIG_CODE_FORMAT, 'alphanum'],
            [self::XML_CONFIG_CODE_LENGTH, 10],
            [self::XML_CONFIG_CODE_SPLIT, 0],
            [self::XML_CONFIG_CODE_PREFIX, ''],
            [self::XML_CONFIG_CODE_SUFFIX, '']
        ];

        $mapPrefix = [
            [self::XML_CONFIG_POOL_SIZE, 50],
            [self::XML_CONFIG_CODE_FORMAT, 'alphanum'],
            [self::XML_CONFIG_CODE_LENGTH, 10],
            [self::XML_CONFIG_CODE_SPLIT, 0],
            [self::XML_CONFIG_CODE_PREFIX, 'S'],
            [self::XML_CONFIG_CODE_SUFFIX, '']
        ];

        $mapSuffix = [
            [self::XML_CONFIG_POOL_SIZE, 50],
            [self::XML_CONFIG_CODE_FORMAT, 'alphanum'],
            [self::XML_CONFIG_CODE_LENGTH, 10],
            [self::XML_CONFIG_CODE_SPLIT, 0],
            [self::XML_CONFIG_CODE_PREFIX, ''],
            [self::XML_CONFIG_CODE_SUFFIX, 'F']
        ];

        $mapBoth = [
            [self::XML_CONFIG_POOL_SIZE, 50],
            [self::XML_CONFIG_CODE_FORMAT, 'alphanum'],
            [self::XML_CONFIG_CODE_LENGTH, 10],
            [self::XML_CONFIG_CODE_SPLIT, 0],
            [self::XML_CONFIG_CODE_PREFIX, 'S'],
            [self::XML_CONFIG_CODE_SUFFIX, 'F']
        ];

        return [
            [$mapNoPrefixOrSuffix, 'TEST123'],
            [$mapPrefix, 'STEST123'],
            [$mapSuffix, 'TEST123F'],
            [$mapBoth, 'STEST123F']
        ];
    }
}
