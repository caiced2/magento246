<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Support\Model\Report\Group\Configuration\AbstractScopedConfigurationSection as ScopedSection;
use Magento\Support\Model\Report\Group\Configuration\ConfigurationSection;

class ConfigurationSectionTest extends AbstractScopedConfigurationSectionTest
{
    /**
     * @var ConfigurationSection
     */
    protected $configurationReport;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configurationReport = $this->objectManagerHelper->getObject(
            ConfigurationSection::class,
            [
                'logger' => $this->loggerMock,
                'config' => $this->configMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testGetReportTitle()
    {
        $this->assertSame((string)__('Configuration'), $this->configurationReport->getReportTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function testGetConfigDataItem()
    {
        $configInfo = [
            'title' => 'Test',
            'enabled_flag' => true,
        ];
        $expectedResult = ['Test', ScopedSection::FLAG_YES, '', 'Test'];
        $this->assertSame($expectedResult, $this->configurationReport->getConfigDataItem(true, $configInfo, 'Test'));
    }

    /**
     * {@inheritdoc}
     */
    public function testGenerate()
    {
        $this->configMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap($this->getExpectedMap());
        $expectedData = $this->getExpectedData();
        $expectedResult = [
            $this->configurationReport->getReportTitle() => [
                'headers' => [(string)__('Name'), (string)__('Enabled'), (string)__('Value'), (string)__('Scope')],
                'data' => array_values($expectedData),
                'count' => count($expectedData)
            ],
        ];
        $this->assertSame($expectedResult, $this->configurationReport->generate());
    }

    /**
     * @return array
     */
    protected function getExpectedMap()
    {
        $testData = $this->getTestData();
        $expectedMap = [
            [
                Custom::XML_PATH_WEB_COOKIE_HTTPONLY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['use_http_only']
            ],
            [
                Custom::XML_PATH_WEB_COOKIE_RESTRICTION,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['cookie_restriction_mode'],
            ],
            [
                Custom::XML_PATH_WEB_SESSION_USE_REMOTE_ADDR,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['validate_remote_addr'],
            ],
            [
                Custom::XML_PATH_WEB_SESSION_USE_HTTP_VIA,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['validate_http_via'],
            ],
            [
                Custom::XML_PATH_WEB_SESSION_USE_HTTP_X_FORWARDED_FOR,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['validate_http_x_forwarded_for'],
            ],
            [
                Custom::XML_PATH_WEB_SESSION_USE_HTTP_USER_AGENT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['validate_http_user_agent'],
            ],
            [
                Custom::XML_PATH_WEB_SESSION_USE_FRONTEND_SID,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['use_sid_on_frontend'],
            ],
            [
                Custom::XML_PATH_SYSTEM_BACKUP_ENABLED,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['system_backup_enabled'],
            ],
            [
                Custom::XML_PATH_DEV_JS_MERGE_FILES,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['merge_js_files']
            ],
            [
                Custom::XML_PATH_DEV_JS_MINIFY_FILES,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['minify_js_files']
            ],
            [
                Custom::XML_PATH_DEV_CSS_MERGE_CSS_FILES,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['merge_css_files'],
            ],
            [
                Custom::XML_PATH_DEV_CSS_MINIFY_FILES,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['minify_css_files'],
            ],
            [
                Custom::XML_PATH_DEV_IMAGE_DEFAULT_ADAPTER,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['default_image_adapter'],
            ],
        ];

        return array_merge(
            $this->getExpectedMapBasePart(),
            $this->getExpectedMapGeneralPart(),
            $this->getExpectedMapCookiePart(),
            $expectedMap
        );
    }

    /**
     * @return array
     */
    protected function getExpectedMapBasePart()
    {
        $testData = $this->getTestData();
        return [
            [
                Custom::XML_PATH_SECURE_BASE_URL,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['secure_base_url']
            ],
            [
                Custom::XML_PATH_UNSECURE_BASE_URL,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['unsecure_base_url']
            ],
            [
                Custom::XML_PATH_CURRENCY_OPTIONS_BASE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['base_currency']
            ],
            [
                Custom::XML_PATH_MAINTENANCE_MODE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['maintenance']
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedMapGeneralPart()
    {
        $testData = $this->getTestData();
        return [
            [
                Custom::XML_PATH_GENERAL_LOCALE_CODE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['locale_code']
            ],
            [
                Custom::XML_PATH_GENERAL_LOCALE_TIMEZONE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['locale_timezone'],
            ],
            [
                Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['default_country'],
            ],
            [
                Custom::XML_PATH_ADMIN_SECURITY_USEFORMKEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['add_secret_key_to_urls'],
            ],
            [
                Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_CATEGORY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['flat_catalog_category'],
            ],
            [
                Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_PRODUCT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['flat_catalog_product'],
            ],
            [
                Custom::XML_PATH_TAX_WEEE_ENABLE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['fixed_product_taxes']
            ],
            [
                Custom::XML_PATH_CATALOG_SEARCH_ENGINE,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['search_engine']
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedMapCookiePart()
    {
        $testData = $this->getTestData();
        return [
            [
                Custom::XML_PATH_WEB_COOKIE_COOKIE_LIFETIME,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['cookie_lifetime'],
            ],
            [
                Custom::XML_PATH_WEB_COOKIE_COOKE_PATH,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['cookie_path']
            ],
            [
                Custom::XML_PATH_WEB_COOKIE_COOKIE_DOMAIN,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                null,
                $testData['cookie_domain'],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedData()
    {
        $testData = $this->getTestData();
        $expectedData = [
            Custom::XML_PATH_WEB_COOKIE_HTTPONLY => [
                'Use HTTP Only',
                ScopedSection::FLAG_YES,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_COOKIE_RESTRICTION => [
                'Cookie Restriction Mode',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_REMOTE_ADDR => [
                'Validate REMOTE_ADDR',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_VIA => [
                'Validate HTTP_VIA',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_X_FORWARDED_FOR => [
                'Validate HTTP_X_FORWARDED_FOR',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_USER_AGENT => [
                'Validate HTTP_USER_AGENT',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_FRONTEND_SID => [
                'Use SID on Frontend',
                ScopedSection::FLAG_YES,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_SYSTEM_BACKUP_ENABLED => [
                'System Backup Enabled',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_DEV_JS_MERGE_FILES => [
                'Merge JavaScript Files',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_DEV_JS_MINIFY_FILES => [
                'Minify Javascript Files',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_DEV_CSS_MERGE_CSS_FILES => [
                'Merge CSS Files',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_DEV_CSS_MINIFY_FILES => [
                'Minify CSS Files',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT
            ],
            Custom::XML_PATH_DEV_IMAGE_DEFAULT_ADAPTER => [
                'Image processing adapter',
                '',
                $testData['default_image_adapter'],
                ScopedSection::SCOPE_DEFAULT,
            ],
        ];
        return array_merge(
            $this->getExpectedDataBasePart(),
            $this->getExpectedDataGeneralPart(),
            $this->getExpectedDataCookiePart(),
            $expectedData
        );
    }

    /**
     * @return array
     */
    protected function getExpectedDataBasePart()
    {
        $testData = $this->getTestData();
        return [
            Custom::XML_PATH_SECURE_BASE_URL => [
                'Base Secured URL',
                '',
                $testData['secure_base_url'],
                ScopedSection::SCOPE_DEFAULT
            ],
            Custom::XML_PATH_UNSECURE_BASE_URL => [
                'Base Unsecured URL',
                '',
                $testData['unsecure_base_url'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_CURRENCY_OPTIONS_BASE => [
                'Base Currency',
                '',
                $testData['base_currency'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_MAINTENANCE_MODE => [
                'Maintenance Mode',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedDataGeneralPart()
    {
        $testData = $this->getTestData();
        return [
            Custom::XML_PATH_GENERAL_LOCALE_CODE => [
                'Locale Code',
                '',
                $testData['locale_code'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_GENERAL_LOCALE_TIMEZONE => [
                'Locale Timezone',
                '',
                $testData['locale_timezone'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT => [
                'Default Country',
                '',
                $testData['default_country'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_ADMIN_SECURITY_USEFORMKEY => [
                'Add Secret Key to URLs',
                ScopedSection::FLAG_YES,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_CATEGORY => [
                'Flat Catalog Category',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_PRODUCT => [
                'Flat Catalog Product',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_TAX_WEEE_ENABLE => [
                'Fixed Product Taxes (FPT)',
                ScopedSection::FLAG_NO,
                '',
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_CATALOG_SEARCH_ENGINE => [
                'Search Engine',
                '',
                $testData['search_engine'],
                ScopedSection::SCOPE_DEFAULT,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedDataCookiePart()
    {
        $testData = $this->getTestData();
        return [
            Custom::XML_PATH_WEB_COOKIE_COOKIE_LIFETIME => [
                'Cookie Lifetime',
                '',
                $testData['cookie_lifetime'],
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_COOKIE_COOKE_PATH => [
                'Cookie Path',
                '',
                null,
                ScopedSection::SCOPE_DEFAULT,
            ],
            Custom::XML_PATH_WEB_COOKIE_COOKIE_DOMAIN => [
                'Cookie Domain',
                '',
                null,
                ScopedSection::SCOPE_DEFAULT,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getTestData()
    {
        return [
            'secure_base_url' => 'https://test.local',
            'unsecure_base_url' => 'http://test.local',
            'base_currency' => 'USD',
            'maintenance' => false,
            'locale_code' => 'en_US',
            'locale_timezone' => 'Europe/Kiev',
            'default_country' => 'US',
            'add_secret_key_to_urls' => true,
            'flat_catalog_category' => false,
            'flat_catalog_product' => false,
            'fixed_product_taxes' => false,
            'search_engine' => 'elasticsearch',
            'cookie_lifetime' => 3600,
            'cookie_path' => null,
            'cookie_domain' => null,
            'use_http_only' => true,
            'cookie_restriction_mode' => false,
            'validate_remote_addr' => false,
            'validate_http_via' => false,
            'validate_http_x_forwarded_for' => false,
            'validate_http_user_agent' => false,
            'use_sid_on_frontend' => true,
            'system_backup_enabled' => false,
            'merge_js_files' => false,
            'minify_js_files' => false,
            'merge_css_files' => false,
            'minify_css_files' => false,
            'default_image_adapter' => 'GD2',
        ];
    }

    /**
     * @return void
     */
    public function testToFlag()
    {
        $this->assertSame(ScopedSection::FLAG_YES, $this->configurationReport->toFlag(true));
        $this->assertSame(ScopedSection::FLAG_NO, $this->configurationReport->toFlag(false));
    }

    /**
     * @return void
     */
    public function testGetByKey()
    {
        $data = ['testKey' => 'testValue'];
        $defaultValue = 'defaultValue';

        $this->assertSame('testValue', $this->configurationReport->getByKey($data, 'testKey'));
        $this->assertSame($defaultValue, $this->configurationReport->getByKey($data, 'inexistentKey', $defaultValue));
    }
}
