<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\App;

use Magento\CatalogPermissions\App\Config;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
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
        $scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $scopeConfigMock->expects(
            $this->once()
        )->method(
            $configMethod
        )->with(
            $path,
            ScopeInterface::SCOPE_STORE,
            null
        )->willReturn(
            $configValue
        );
        $model = new Config($scopeConfigMock);
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
                ['0'],
                '0'
            ],
            [
                'getCatalogProductPriceGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CATALOG_PRODUCT_PRICE . '_groups',
                ['value1', 'value2'],
                'value1,value2'
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
                ['0'],
                '0'
            ],
            [
                'getCheckoutItemsGroups',
                'getValue',
                ConfigInterface::XML_PATH_GRANT_CHECKOUT_ITEMS . '_groups',
                ['value1', 'value2'],
                'value1,value2'
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
                ['0'],
                '0'
            ],
            [
                'getCatalogSearchDenyGroups',
                'getValue',
                ConfigInterface::XML_PATH_DENY_CATALOG_SEARCH,
                ['value1', 'value2'],
                'value1,value2'
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
