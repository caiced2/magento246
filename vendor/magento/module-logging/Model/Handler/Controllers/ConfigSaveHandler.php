<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Model\Handler\Controllers;

use Magento\Logging\Observer\AdminSystemConfigSavePredispatchObserver;
use Magento\Config\Model\Config\Backend\Encrypted;

class ConfigSaveHandler
{
    public const KEY_STORE_CONFIG_ORIGINAL_VALUES = 'store_config_original_values';

    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    private $structureConfig;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Logging\Model\Event\ChangesFactory
     */
    private $eventChangesFactory;

    /**
     * @param \Magento\Config\Model\Config\Structure $structureConfig
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory
     */
    public function __construct(
        \Magento\Config\Model\Config\Structure $structureConfig,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory
    ) {
        $this->structureConfig = $structureConfig;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->eventChangesFactory = $eventChangesFactory;
    }

    /**
     * Execute custom handler for config save
     *
     * @param \Magento\Logging\Model\Event $eventModel
     * @param \Magento\Logging\Model\Processor $processor
     */
    public function execute(
        \Magento\Logging\Model\Event $eventModel,
        \Magento\Logging\Model\Processor $processor
    ): void {
        $postData = $this->request->getPostValue();

        $sectionId = $this->request->getParam('section') ?? 'general';
        $eventModel->setInfo($sectionId);

        if (!isset($postData['groups']) || !is_array($postData['groups'])) {
            return;
        }

        $skippedEncryptedFields = $this->getSkippedEncryptedFields();

        $originalData = $this->getOriginalData();

        foreach ($postData['groups'] as $groupName => $groupData) {
            $this->createEventChangeEntryForGroup(
                $processor,
                $sectionId,
                $skippedEncryptedFields,
                $originalData,
                $groupName,
                $groupData
            );
        }
    }

    /**
     * Get node paths for encrypted fields
     *
     * @return array
     */
    private function getSkippedEncryptedFields(): array
    {
        $encryptedNodePaths = $this->structureConfig->getFieldPathsByAttribute('backend_model', Encrypted::class);

        $encryptedFields = [];
        foreach ($encryptedNodePaths as $path) {
            $encryptedFields[] = substr($path, strrpos($path, '/') + 1);
        }

        return $encryptedFields;
    }

    /**
     * Get original data for config values before they were saved with new values
     *
     * @return array
     */
    private function getOriginalData(): array
    {
        $originalData = [];

        if ($this->dataPersistor->get(self::KEY_STORE_CONFIG_ORIGINAL_VALUES)) {
            $originalData = $this->dataPersistor->get(
                self::KEY_STORE_CONFIG_ORIGINAL_VALUES
            );
            $this->dataPersistor->clear(self::KEY_STORE_CONFIG_ORIGINAL_VALUES);
        }

        return $originalData;
    }

    /**
     * Create event chaneg entry for a config group
     *
     * @param \Magento\Logging\Model\Processor $processor
     * @param string $sectionId
     * @param array $skippedEncryptedFields
     * @param array $originalData
     * @param string $groupName
     * @param array $groupData
     */
    private function createEventChangeEntryForGroup(
        \Magento\Logging\Model\Processor $processor,
        string $sectionId,
        array $skippedEncryptedFields,
        array $originalData,
        string $groupName,
        array $groupData
    ): void {
        $groupOriginalData = [];
        $groupFieldsData = [];
        if (!isset($groupData['fields']) || !is_array($groupData['fields'])) {
            return;
        }
        foreach ($groupData['fields'] as $fieldName => $fieldValueData) {
            //Clearing config data accordingly to collected skip fields
            if (in_array($fieldName, $skippedEncryptedFields)) {
                continue;
            }

            $fieldPath = sprintf('%s/%s/%s', $sectionId, $groupName, $fieldName);
            $originalValue = $originalData[$fieldPath] ?? null;
            $newValue = $fieldValueData['value'] ?? $originalValue;

            if (isset($fieldValueData['inherit'])) {
                $newValue = $originalValue;
            }

            if ($originalValue != $newValue) {
                $groupOriginalData[$fieldName] = $originalValue;
                $groupFieldsData[$fieldName] = $newValue;
            }
        }

        /** @var \Magento\Logging\Model\Event\Changes $change */
        $change = $this->eventChangesFactory->create();

        $processor->addEventChanges(
            $change->setSourceName($groupName)
                ->setOriginalData($groupOriginalData)
                ->setResultData($groupFieldsData)
        );
    }
}
