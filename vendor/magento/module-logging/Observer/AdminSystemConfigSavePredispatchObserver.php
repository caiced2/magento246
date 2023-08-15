<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Logging\Model\Handler\Controllers\ConfigSaveHandler;
use Magento\Store\Model\ScopeInterface;

class AdminSystemConfigSavePredispatchObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Persist current config data values before a save that will possibly overwrite them with new values
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $request = $observer->getRequest();
        $groups = $request->getPostValue('groups');
        if (!is_array($groups)) {
            return;
        }

        $sectionId = $request->getParam('section') ?? 'general';
        $scopeData = ['scope' => 'default', 'id' => null];

        if ($request->getParam('website')) {
            $scopeData = ['scope' => ScopeInterface::SCOPE_WEBSITE, 'id' => $request->getParam('website')];
        }

        if ($request->getParam('store')) {
            $scopeData = ['scope' => ScopeInterface::SCOPE_STORE, 'id' => $request->getParam('store')];
        }

        $originalData = [];
        foreach ($groups as $groupName => $groupData) {
            if (!isset($groupData['fields']) || !is_array($groupData['fields'])) {
                continue;
            }
            foreach (array_keys($groupData['fields']) as $fieldName) {
                $fieldPath = sprintf('%s/%s/%s', $sectionId, $groupName, $fieldName);
                $originalData[$fieldPath] = $this->scopeConfig->getValue(
                    $fieldPath,
                    $scopeData['scope'],
                    $scopeData['id']
                );
            }
        }

        $this->dataPersistor->set(ConfigSaveHandler::KEY_STORE_CONFIG_ORIGINAL_VALUES, $originalData);
    }
}
