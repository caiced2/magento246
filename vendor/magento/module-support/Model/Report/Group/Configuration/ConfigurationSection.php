<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;

/**
 * Configuration section model
 */
class ConfigurationSection extends AbstractScopedConfigurationSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $configPaths = $this->getConfigPaths();
        $configData = $this->prepareConfigValues($configPaths);

        return [
            $this->getReportTitle() => [
                'headers' => [(string)__('Name'), (string)__('Enabled'), (string)__('Value'), (string)__('Scope')],
                'data' => $configData,
                'count' => count($configData),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigDataItem($value, array $configInfo, $scopeName)
    {
        return [
            $configInfo['title'],
            (!empty($configInfo['enabled_flag']) ? $this->toFlag($value) : ''),
            !empty($configInfo['enabled_flag']) ? '' : $value,
            $scopeName,
        ];
    }

    /**
     * Collecting configuration paths
     *
     * @return array
     */
    protected function getConfigPaths()
    {
        $paths = [
            Custom::XML_PATH_SECURE_BASE_URL => ['title' => 'Base Secured URL'],
            Custom::XML_PATH_UNSECURE_BASE_URL => ['title' => 'Base Unsecured URL'],
            Custom::XML_PATH_CURRENCY_OPTIONS_BASE => ['title' => 'Base Currency'],
            Custom::XML_PATH_MAINTENANCE_MODE => ['title' => 'Maintenance Mode', 'enabled_flag' => true],
            Custom::XML_PATH_GENERAL_LOCALE_CODE => ['title' => 'Locale Code'],
            Custom::XML_PATH_GENERAL_LOCALE_TIMEZONE => ['title' => 'Locale Timezone'],
            Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT => ['title' => 'Default Country'],
            Custom::XML_PATH_ADMIN_SECURITY_USEFORMKEY => [
                'title' => 'Add Secret Key to URLs',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_CATEGORY => [
                'title' => 'Flat Catalog Category',
                'enabled_flag' => true,
            ],
            Custom::XML_PATH_CATALOG_FRONTEND_FLAT_CATALOG_PRODUCT => [
                'title' => 'Flat Catalog Product',
                'enabled_flag' => true,
            ],
            Custom::XML_PATH_TAX_WEEE_ENABLE => [
                'title' => 'Fixed Product Taxes (FPT)',
                'enabled_flag' => true,
            ],
            Custom::XML_PATH_CATALOG_SEARCH_ENGINE => [
                'title' => 'Search Engine',
            ],
            Custom::XML_PATH_WEB_COOKIE_COOKIE_LIFETIME => ['title' => 'Cookie Lifetime'],
            Custom::XML_PATH_WEB_COOKIE_COOKE_PATH => ['title' => 'Cookie Path'],
            Custom::XML_PATH_WEB_COOKIE_COOKIE_DOMAIN => ['title' => 'Cookie Domain'],
            Custom::XML_PATH_WEB_COOKIE_HTTPONLY => ['title' => 'Use HTTP Only', 'enabled_flag' => true],
            Custom::XML_PATH_WEB_COOKIE_RESTRICTION => [
                'title' => 'Cookie Restriction Mode',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_WEB_SESSION_USE_REMOTE_ADDR => [
                'title' => 'Validate REMOTE_ADDR',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_VIA => [
                'title' => 'Validate HTTP_VIA',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_X_FORWARDED_FOR => [
                'title' => 'Validate HTTP_X_FORWARDED_FOR',
                'enabled_flag' => true,
            ],
            Custom::XML_PATH_WEB_SESSION_USE_HTTP_USER_AGENT => [
                'title' => 'Validate HTTP_USER_AGENT',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_WEB_SESSION_USE_FRONTEND_SID => [
                'title' => 'Use SID on Frontend',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_SYSTEM_BACKUP_ENABLED => [
                'title' => 'System Backup Enabled',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_DEV_JS_MERGE_FILES => [
                'title' => 'Merge JavaScript Files',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_DEV_JS_MINIFY_FILES => [
                'title' => 'Minify Javascript Files',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_DEV_CSS_MERGE_CSS_FILES => ['title' => 'Merge CSS Files', 'enabled_flag' => true],
            Custom::XML_PATH_DEV_CSS_MINIFY_FILES => [
                'title' => 'Minify CSS Files',
                'enabled_flag' => true
            ],
            Custom::XML_PATH_DEV_IMAGE_DEFAULT_ADAPTER => ['title' => 'Image processing adapter'],
        ];
        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportTitle()
    {
        return (string)__('Configuration');
    }
}
