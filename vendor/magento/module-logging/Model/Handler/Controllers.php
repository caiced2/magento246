<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Handles generic and specific logic for logging on pre/postdispatch
 *
 * All action handlers may take the $config and $eventModel params, which are configuration node for current action and
 * the event model respectively
 * Action will be logged only if the handler returns non-empty value
 */
namespace Magento\Logging\Model\Handler;

use Magento\Logging\Model\Event;
use Magento\Logging\Model\Processor;

/**
 * Controller for define specific logic for logging on pre/postdispatch
 */
class Controllers
{
    /**
     * @var \Magento\Logging\Helper\Data
     */
    protected $_loggingData = null;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper = null;

    /**
     * @var \Magento\Catalog\Helper\Product\Edit\Action\Attribute
     */
    protected $_actionAttribute = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * Factory for event changes model
     *
     * @var \Magento\Logging\Model\Event\ChangesFactory
     */
    protected $_eventChangesFactory;

    /**
     * @var Controllers\ConfigSaveHandler
     */
    private $configSaveHandler;

    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Logging\Helper\Data $loggingData
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Catalog\Helper\Product\Edit\Action\Attribute $actionAttribute
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory
     * @param \Magento\Logging\Model\Handler\Controllers\ConfigSaveHandler $configSaveHandler
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Logging\Helper\Data $loggingData,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Catalog\Helper\Product\Edit\Action\Attribute $actionAttribute,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory,
        \Magento\Logging\Model\Handler\Controllers\ConfigSaveHandler $configSaveHandler
    ) {
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_loggingData = $loggingData;
        $this->jsonHelper = $jsonHelper;
        $this->_actionAttribute = $actionAttribute;
        $this->_request = $request;
        $this->_response = $response;
        $this->_eventChangesFactory = $eventChangesFactory;
        $this->configSaveHandler = $configSaveHandler;
    }

    /**
     * Generic Action handler
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processorModel
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchGeneric($config, $eventModel, $processorModel)
    {
        $collectedIds = $processorModel->getCollectedIds();
        if ($collectedIds) {
            $eventModel->setInfo($this->_loggingData->implodeValues($collectedIds));
        }
        return true;
    }

    /**
     * Simply log action without any id-s
     *
     * @param array $config
     * @param Event $eventModel
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSimpleSave($config, $eventModel)
    {
        return true;
    }

    /**
     * Custom handler for config view
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchConfigView($config, $eventModel)
    {
        $sectionId = $this->_request->getParam('section');
        if (!$sectionId) {
            $sectionId = 'general';
        }
        $eventModel->setInfo($sectionId);
        return true;
    }

    /**
     * Custom handler for config save
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchConfigSave($config, $eventModel, $processor): bool
    {
        $this->configSaveHandler->execute($eventModel, $processor);

        return true;
    }

    /**
     * Custom handler for category move
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchCategoryMove($config, $eventModel)
    {
        return $eventModel->setInfo($this->_request->getParam('id'));
    }

    /**
     * Custom handler for global search
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchGlobalSearch($config, $eventModel)
    {
        return $eventModel->setInfo($this->_request->getParam('query'));
    }

    /**
     * Handler for forgotpassword
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchForgotPassword($config, $eventModel)
    {
        if ($this->_request->isPost()) {
            if ($model = $this->_coreRegistry->registry('magento_logging_saved_model_adminhtml_index_forgotpassword')
            ) {
                $info = $model->getId();
            } else {
                $info = $this->_request->getParam('email');
            }
            $success = true;
            $messages = $this->messageManager->getMessages()->getLastAddedMessage();
            if ($messages) {
                $success = 'error' != $messages->getType();
            }
            return $eventModel->setIsSuccess($success)->setInfo($info);
        }
        return false;
    }

    /**
     * Custom handler for customer validation fail's action
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchCustomerValidate($config, $eventModel)
    {
        $out = json_decode($this->_response->getBody());
        if (!empty($out->error)) {
            $customerId = $this->_request->getParam('id');
            return $eventModel->setIsSuccess(false)->setInfo($customerId == 0 ? '' : $customerId);
        }
        return false;
    }

    /**
     * Handler for reports
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event|bool
     */
    public function postDispatchReport($config, $eventModel, $processor)
    {
        $fullActionNameParts = explode('_report_', $config['controller_action'], 2);
        if (empty($fullActionNameParts[1])) {
            return false;
        }

        $filter = $this->_request->getParam('filter');

        //Filtering request data
        $data = array_intersect_key(
            $this->_request->getParams(),
            [
                'report_from' => null,
                'report_to' => null,
                'report_period' => null,
                'store' => null,
                'website' => null,
                'group' => null
            ]
        );

        //Need when in request data there are was no period info
        if ($filter) {
            $filterData = $this->_actionAttribute->prepareFilterString($filter);
            $data = array_merge($data, (array)$filterData);
        }

        //Add log entry details
        if ($data) {
            /** @var \Magento\Logging\Model\Event\Changes $change */
            $change = $this->_eventChangesFactory->create();
            $processor->addEventChanges(
                $change->setSourceName('params')->setOriginalData([])->setResultData($data)
            );
        }

        return $eventModel->setInfo($fullActionNameParts[1]);
    }

    /**
     * Custom handler for catalog price rules apply
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchPromoCatalogApply($config, $eventModel)
    {
        return $eventModel->setInfo(
            $this->_request->getParam('rule_id') ? $this->_request->getParam('rule_id') : 'all rules'
        );
    }

    /**
     * Custom handler for catalog price rules save & apply
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processorModel
     * @return Event
     */
    public function postDispatchPromoCatalogSaveAndApply($config, $eventModel, $processorModel)
    {
        $this->postDispatchGeneric($config, $eventModel, $processorModel);
        if ($this->_request->getParam('auto_apply')) {
            $eventModel->setInfo(__('%1 & applied', $eventModel->getInfo()));
        }

        return $eventModel;
    }

    /**
     * Special handler for newsletter unsubscribe
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchNewsletterUnsubscribe($config, $eventModel)
    {
        $subscriberId = $this->_request->getParam('subscriber');
        if (is_array($subscriberId)) {
            $subscriberId = implode(', ', $subscriberId);
        }
        return $eventModel->setInfo($subscriberId);
    }

    /**
     * Custom tax import handler
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchTaxRatesImport($config, $eventModel)
    {
        if (!$this->_request->isPost()) {
            return false;
        }
        $success = true;
        $messages = $this->messageManager->getMessages()->getLastAddedMessage();
        if ($messages) {
            $success = 'error' != $messages->getType();
        }
        return $eventModel->setIsSuccess($success)->setInfo(__('Tax Rates Import'));
    }

    /**
     * Custom handler for catalog product mass attribute update
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchProductUpdateAttributes($config, $eventModel, $processor)
    {
        /** @var \Magento\Logging\Model\Event\Changes $change */
        $change = $this->_eventChangesFactory->create();
        $products = $this->_request->getParam('product');
        if (!$products) {
            $products = $this->_actionAttribute->getProductIds();
        }
        if ($products) {
            $processor->addEventChanges(
                clone $change->setSourceName(
                    'product'
                )->setOriginalData(
                    []
                )->setResultData(
                    ['ids' => implode(', ', $products)]
                )
            );
        }

        $processor->addEventChanges(
            clone $change->setSourceName(
                'inventory'
            )->setOriginalData(
                []
            )->setResultData(
                $this->_request->getParam('inventory', [])
            )
        );
        $attributes = $this->_request->getParam('attributes', []);
        $status = $this->_request->getParam('status', null);
        if (!$attributes && $status) {
            $attributes['status'] = $status;
        }
        $processor->addEventChanges(
            clone $change->setSourceName('attributes')->setOriginalData([])->setResultData($attributes)
        );

        $websiteIds = $this->_request->getParam('remove_website', []);
        if ($websiteIds) {
            $processor->addEventChanges(
                clone $change->setSourceName(
                    'remove_website_ids'
                )->setOriginalData(
                    []
                )->setResultData(
                    ['ids' => implode(', ', $websiteIds)]
                )
            );
        }

        $websiteIds = $this->_request->getParam('add_website', []);
        if ($websiteIds) {
            $processor->addEventChanges(
                clone $change->setSourceName(
                    'add_website_ids'
                )->setOriginalData(
                    []
                )->setResultData(
                    ['ids' => implode(', ', $websiteIds)]
                )
            );
        }

        return $eventModel->setInfo(__('Attributes Updated'));
    }

    /**
     * Custom switcher for tax_class_save, to distinguish product and customer tax classes
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchTaxClassSave($config, $eventModel)
    {
        if (!$this->_request->isPost()) {
            return false;
        }
        $classType = $this->_request->getParam('class_type');
        $classId = (int)$this->_request->getParam('class_id');

        return $this->_logTaxClassEvent($classType, $eventModel, $classId);
    }

    /**
     * Custom switcher for tax_class_save, to distinguish product and customer tax classes
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchTaxClassDelete($config, $eventModel)
    {
        if (!$this->_request->isPost()) {
            return false;
        }
        $classId = (int)$this->_request->getParam('class_id');
        return $this->_logTaxClassEvent('', $eventModel, $classId);
    }

    /**
     * Custom handler for creating System Backup
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSystemBackupsCreate($config, $eventModel)
    {
        $backup = $this->_coreRegistry->registry('backup_manager');

        if ($backup) {
            $eventModel->setIsSuccess($backup->getIsSuccess())->setInfo($backup->getBackupFilename());

            $errorMessage = $backup->getErrorMessage();
            if (!empty($errorMessage)) {
                $eventModel->setErrorMessage($errorMessage);
            }
        } else {
            $eventModel->setIsSuccess(false);
        }
        return $eventModel;
    }

    /**
     * Custom handler for deleting System Backup
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSystemBackupsDelete($config, $eventModel)
    {
        $backup = $this->_coreRegistry->registry('backup_manager');

        if ($backup) {
            $eventModel->setIsSuccess(
                $backup->getIsSuccess()
            )->setInfo(
                $this->_loggingData->implodeValues($backup->getDeleteResult())
            );
        } else {
            $eventModel->setIsSuccess(false);
        }
        return $eventModel;
    }

    /**
     * Custom handler for creating System Rollback
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSystemRollback($config, $eventModel)
    {
        $backup = $this->_coreRegistry->registry('backup_manager');

        if ($backup) {
            $eventModel->setIsSuccess($backup->getIsSuccess())->setInfo($backup->getBackupFilename());

            $errorMessage = $backup->getErrorMessage();
            if (!empty($errorMessage)) {
                $eventModel->setErrorMessage($errorMessage);
            }
        } else {
            $eventModel->setIsSuccess(false);
        }

        return $eventModel;
    }

    /**
     * Custom handler for mass reindex process on index management
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchReindexProcess($config, $eventModel)
    {
        $processIds = $this->_request->getParam('process', null);
        if (!$processIds) {
            return false;
        }
        return $eventModel->setInfo(is_array($processIds) ? implode(', ', $processIds) : (int)$processIds);
    }

    /**
     * Custom handler for System Currency save
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSystemCurrencySave($config, $eventModel, $processor)
    {
        /** @var \Magento\Logging\Model\Event\Changes $change */
        $change = $this->_eventChangesFactory->create();
        $data = $this->_request->getParam('rate');
        $values = [];
        if (!is_array($data)) {
            return false;
        }
        foreach ($data as $currencyCode => $rate) {
            foreach ($rate as $currencyTo => $value) {
                $value = abs((float) $value);
                if ($value == 0) {
                    continue;
                }
                $values[] = $currencyCode . '=>' . $currencyTo . ': ' . $value;
            }
        }

        $processor->addEventChanges(
            $change->setSourceName(
                'rates'
            )->setOriginalData(
                []
            )->setResultData(
                ['rates' => implode(', ', $values)]
            )
        );
        $success = true;
        $messages = $this->messageManager->getMessages()->getLastAddedMessage();
        if ($messages) {
            $success = 'error' != $messages->getType();
        }
        return $eventModel->setIsSuccess($success)->setInfo(__('Currency Rates Saved'));
    }

    /**
     * Custom handler for Cache Settings Save
     *
     * @param array $config
     * @param Event $eventModel
     * @param Processor $processor
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSaveCacheSettings($config, $eventModel, $processor)
    {
        if (!$this->_request->isPost()) {
            return false;
        }
        $info = '-';
        $cacheTypes = $this->_request->getPost('types');
        if (is_array($cacheTypes) && !empty($cacheTypes)) {
            $cacheTypes = implode(', ', $cacheTypes);
            $info = __('Cache types: %1 ', $cacheTypes);
        }

        $success = true;
        $messages = $this->messageManager->getMessages()->getLastAddedMessage();
        if ($messages) {
            $success = 'error' != $messages->getType();
        }
        return $eventModel->setIsSuccess($success)->setInfo($info);
    }

    /**
     * Custom tax export handler
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event|bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchTaxRatesExport($config, $eventModel)
    {
        if (!$this->_request->isPost()) {
            return false;
        }
        $success = true;
        $messages = $this->messageManager->getMessages()->getLastAddedMessage();
        if ($messages) {
            $success = 'error' != $messages->getType();
        }
        return $eventModel->setIsSuccess($success)->setInfo(__('Tax Rates Export'));
    }

    /**
     * Custom handler for sales archive operations
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchSalesArchiveManagement($config, $eventModel)
    {
        $ids = $this->_request->getParam('order_id', $this->_request->getParam('order_ids'));
        if (is_array($ids)) {
            $ids = implode(', ', $ids);
        }
        return $eventModel->setInfo($ids);
    }

    /**
     * Log tax class event
     *
     * @param string $classType
     * @param Event $eventModel
     * @param int $classId
     *
     * @return Event
     */
    protected function _logTaxClassEvent($classType, $eventModel, $classId)
    {
        if ($classType == 'PRODUCT') {
            $eventModel->setEventCode('tax_product_tax_classes');
        }

        $success = true;
        $messages = null;
        $body = $this->_response->getBody();
        if ($body) {
            $messages = $this->jsonHelper->jsonDecode($body);
        }
        if ($messages && !empty($messages['success'])) {
            $success = $messages['success'];
            if (empty($classId) && !empty($messages['class_id'])) {
                $classId = $messages['class_id'];
            }
        }

        $messageInfo = $classType . ($classId ? ': #' . $classId : '');
        return $eventModel->setIsSuccess($success)->setInfo($messageInfo);
    }

    /**
     * Custom handler for logging indexer operation
     *
     * @param array $config
     * @param Event $eventModel
     * @return Event|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchIndexerIds($config, $eventModel)
    {
        $indexerItems = $this->_request->getParam('indexer_ids');
        if (!empty($indexerItems)) {
            return $eventModel->setInfo(implode(', ', $indexerItems));
        }
    }
}
