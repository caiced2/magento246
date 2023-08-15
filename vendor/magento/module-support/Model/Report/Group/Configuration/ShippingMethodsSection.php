<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;

/**
 * Shipping methods section model
 */
class ShippingMethodsSection extends AbstractScopedConfigurationSection
{
    /**
     * {@inheritdoc}
     */
    public function getReportTitle()
    {
        return (string)__('Shipping Methods');
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $configPaths = $this->getConfigPaths();
        $data = $this->prepareConfigValues($configPaths);

        return [
            $this->getReportTitle() => [
                'headers' => [
                    (string)__('Code'),
                    (string)__('Name'),
                    (string)__('Title'),
                    (string)__('Enabled'),
                    (string)__('Scope')
                ],
                'data' => $data,
                'count' => count($data),
            ]
        ];
    }

    /**
     * Generating paths for each of available shipping mehod
     *
     * @return array
     */
    protected function getConfigPaths()
    {
        if (!($carriers = $this->config->getValue(Custom::XML_PATH_CARRIERS))) {
            return [];
        }

        $configPaths = [];
        foreach ($carriers as $code => $carrierInfo) {
            $configPaths['carriers/' . $code . '/active'] = [
                'title' => (string)$this->getByKey($carrierInfo, 'title', $code),
                'extra' => [
                    'code' => (string)$code,
                    'title' => (string)$this->getByKey($carrierInfo, 'title', ''),
                    'name' => (string)$this->getByKey($carrierInfo, 'name', ''),
                ]
            ];
        }
        return $configPaths;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigDataItem($value, array $configInfo, $scopeName)
    {
        return [
            $configInfo['extra']['code'],
            $configInfo['extra']['name'],
            $configInfo['extra']['title'],
            $this->toFlag($value),
            $scopeName,
        ];
    }
}
