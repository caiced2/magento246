<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Payment methods section model
 */
class PaymentMethodsSection extends AbstractScopedConfigurationSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $paths = $this->getConfigPaths();
        $data = $this->prepareConfigValues($paths);
        return [
            $this->getReportTitle() => [
                'headers' => [
                    (string)__('Code'),
                    (string)__('Group'),
                    (string)__('Title'),
                    (string)__('Enabled'),
                    (string)__('Scope'),
                ],
                'data' => $data,
                'count' => count($data),
            ]
        ];
    }

    /**
     * Collecting configuration paths
     *
     * @return array
     */
    protected function getConfigPaths()
    {
        if (!($methods = $this->config->getValue(Custom::XML_PATH_PAYMENT))) {
            return [];
        }

        $paths = [];
        foreach ($methods as $code => $info) {
            $group = (string)$this->getByKey($info, 'group', '');
            $paths[Custom::XML_PATH_PAYMENT . '/' . $code . '/active'] = [
                'title' => (string)$this->getByKey($info, 'title', $code),
                'enabled_flag' => true,
                'extra' => [
                    'group' => (string)$group,
                    'code' => (string)$code
                ],
            ];
        }
        return $paths;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConfigValues(array $configPaths)
    {
        $firstMethods = $nextMethods = [];

        foreach ($configPaths as $path => $info) {
            $group = (string)$this->getByKey($info, 'group', '');

            if (!$group || in_array($group, [AbstractMethod::GROUP_OFFLINE], true)) {
                $firstMethods[$path] = $info;
            } else {
                $nextMethods[$path] = $info;
            }
        }
        return array_merge(parent::prepareConfigValues($firstMethods), parent::prepareConfigValues($nextMethods));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigDataItem($value, array $configInfo, $scopeName)
    {
        return [
            $configInfo['extra']['code'],
            $configInfo['extra']['group'],
            $configInfo['title'],
            (!empty($configInfo['enabled_flag']) ? $this->toFlag($value) : ''),
            $scopeName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getReportTitle()
    {
        return (string)__('Payment Methods');
    }
}
