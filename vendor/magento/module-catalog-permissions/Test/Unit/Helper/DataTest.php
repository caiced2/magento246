<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Helper;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $model;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createPartialMock(
            Session::class,
            ['getCustomerGroupId']
        );

        $this->configMock = $this->getMockForAbstractClass(
            ConfigInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->urlBuilderMock = $this->getMockForAbstractClass(
            UrlInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Data::class,
            [
                'config' => $this->configMock,
                'customerSession' => $this->sessionMock,
                'urlBuilder' => $this->urlBuilderMock
            ]
        );
    }

    /**
     * @param string $method
     * @param string $modeMethod
     * @param string $groupsMethod
     * @param string $mode
     * @param string[] $groups
     * @param int|null $customerGroupId
     * @param bool $result
     * @dataProvider dataProviderIsGrantMethods
     */
    public function testIsGrantMethods($method, $modeMethod, $groupsMethod, $mode, $groups, $customerGroupId, $result)
    {
        $this->configMock->expects($this->once())->method($modeMethod)->with('store')->willReturn($mode);
        $this->configMock->expects(
            $this->once()
        )->method(
            $groupsMethod
        )->with(
            'store'
        )->willReturn(
            $groups
        );
        $this->sessionMock->expects(
            $this->any()
        )->method(
            'getCustomerGroupId'
        )->willReturn(
            $customerGroupId
        );
        $this->assertEquals($result, $this->model->{$method}('store', $customerGroupId));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderIsGrantMethods()
    {
        return [
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                ConfigInterface::GRANT_NONE,
                [],
                1,
                false,
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                ConfigInterface::GRANT_ALL,
                [],
                2,
                true
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                3,
                false
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                0,
                false
            ],
            [
                'isAllowedCategoryView',
                'getCatalogCategoryViewMode',
                'getCatalogCategoryViewGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                1,
                true
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                ConfigInterface::GRANT_NONE,
                [],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                ConfigInterface::GRANT_ALL,
                [],
                null,
                true
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                null,
                false
            ],
            [
                'isAllowedProductPrice',
                'getCatalogProductPriceMode',
                'getCatalogProductPriceGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                1,
                true
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                ConfigInterface::GRANT_NONE,
                ['1', '2'],
                1,
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                ConfigInterface::GRANT_ALL,
                ['1'],
                1,
                true
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                [],
                null,
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                '0',
                false
            ],
            [
                'isAllowedCheckoutItems',
                'getCheckoutItemsMode',
                'getCheckoutItemsGroups',
                ConfigInterface::GRANT_CUSTOMER_GROUP,
                ['1', '2'],
                '1',
                true
            ]
        ];
    }

    /**
     * @param string[] $groups
     * @param int|null $customerGroupId
     * @param bool $result
     * @dataProvider dataProviderIsAllowedCatalogSearch
     */
    public function testIsAllowedCatalogSearch($groups, $customerGroupId, $result)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getCatalogSearchDenyGroups'
        )->willReturn(
            $groups
        );
        $this->sessionMock->expects(
            $this->any()
        )->method(
            'getCustomerGroupId'
        )->willReturn(
            $customerGroupId
        );
        $this->assertEquals($result, $this->model->isAllowedCatalogSearch());
    }

    /**
     * @return array
     */
    public function dataProviderIsAllowedCatalogSearch()
    {
        return [
            [[], 1, true],
            [[], null, true],
            [['1', '2'], null, true],
            [['1', '2'], 3, true],
            [['1', '2'], 1, false]
        ];
    }

    public function testGetLandingPageUrl()
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getRestrictedLandingPage'
        )->willReturn(
            'some uri'
        );
        $this->urlBuilderMock->expects(
            $this->once()
        )->method(
            'getUrl'
        )->with(
            '',
            ['_direct' => 'some uri']
        )->willReturn(
            'some url'
        );
        $this->assertEquals('some url', $this->model->getLandingPageUrl());
    }
}
