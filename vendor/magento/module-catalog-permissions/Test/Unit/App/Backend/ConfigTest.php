<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\App\Backend;

use Magento\CatalogPermissions\App\Backend\Config;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class to test the config Catalog Permissions
 */
class ConfigTest extends TestCase
{
    /**
     * @param string $method
     * @param string $configMethod
     * @param string $path
     * @param string|string[]|bool $value
     * @param string|bool $configValue
     * @dataProvider dataProviderMethods
     */
    public function testMethods($method, $configMethod, $path, $value, $configValue)
    {
        $configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $configMock->expects(
            $this->once()
        )->method(
            $configMethod
        )->with(
            $path,
            'default'
        )->willReturn(
            $configValue
        );
        $model = new Config($configMock);
        $this->assertEquals($value, $model->{$method}());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderMethods()
    {
        return [
            [
                'isEnabled',
                'isSetFlag',
                ConfigInterface::XML_PATH_ENABLED,
                true,
                true,
            ],
            [
                'isEnabled',
                'isSetFlag',
                ConfigInterface::XML_PATH_ENABLED,
                false,
                false
            ],
            [
                'getCatalogCategoryViewMode',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW,
                'value',
                'value'
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                [],
                ''
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCatalogCategoryViewGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_CATEGORY_VIEW . '_groups',
                ['0'],
                '0'
            ],
            [
                'getCatalogProductPriceMode',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE,
                'value',
                'value'
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                [],
                ''
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                ['0'],
                '0'
            ],
            [
                'getCheckoutItemsMode',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS,
                'value',
                'value'
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                [],
                ''
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                ['0'],
                '0'
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                [],
                null
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                ['value1', 'value2'],
                'value1,value2'
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                ['0'],
                '0'
            ],
            [
                'getRestrictedLandingPage',
                'getValue',
                ConfigInterface::XML_PATH_LANDING_PAGE,
                'value',
                'value'
            ]
        ];
    }
}
