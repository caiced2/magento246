<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\Handler\Controllers as ControllersLoggingHandler;
use Magento\Logging\Model\Handler\ControllersFactory as LoggingHandlerFactory;

/**
 * Logging processor model
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Processor
{
    /**
     * Logging events config
     *
     * @var \Magento\Logging\Model\Config
     */
    protected $_config;

    /**
     * current event config
     *
     * @var array
     */
    protected $_eventConfig;

    /**
     * Instance of controller handler
     *
     * @var ControllersLoggingHandler
     */
    protected $_controllersHandler;

    /**
     * Instance of model controller
     *
     * @var \Magento\Logging\Model\Handler\Models
     */
    protected $_modelsHandler;

    /**
     * Last action name
     *
     * @var string
     */
    protected $_actionName = '';

    /**
     * Last full action name
     *
     * @var string
     */
    protected $_lastAction = '';

    /**
     * Initialization full action name
     *
     * @var string
     */
    protected $_initAction = '';

    /**
     * Flag that signal that we should skip next action
     *
     * @var bool
     */
    protected $_skipNextAction = false;

    /**
     * Temporary storage for model changes before saving to magento_logging_event_changes table.
     *
     * @var Event\Changes[]
     */
    protected $_eventChanges = [];

    /**
     * Collection of affected ids
     *
     * @var array
     */
    protected $_collectedIds = [];

    /**
     * Collection of additional data
     *
     * @var array
     */
    protected $_additionalData = [];

    /**
     * Backend auth session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Logger model
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Event model factory
     *
     * @var \Magento\Logging\Model\EventFactory
     */
    protected $_eventFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @var \Magento\Logging\Model\Event\ChangesFactory
     */
    protected $changesFactory;

    /**
     * @var LoggingHandlerFactory
     */
    private $handlerControllersFactory;

    /**
     * Constructor: initialize configuration model, controller and model handler
     *
     * @param \Magento\Logging\Model\Config $config
     * @param \Magento\Logging\Model\Handler\Models $modelsHandler
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param LoggingHandlerFactory $handlerControllersFactory
     * @param \Magento\Logging\Model\EventFactory $eventFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Logging\Model\Event\ChangesFactory $changesFactory
     * @param ControllersLoggingHandler $controllersHandler
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Logging\Model\Config $config,
        \Magento\Logging\Model\Handler\Models $modelsHandler,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        LoggingHandlerFactory $handlerControllersFactory,
        \Magento\Logging\Model\EventFactory $eventFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Logging\Model\Event\ChangesFactory $changesFactory,
        ControllersLoggingHandler $controllersHandler = null
    ) {
        $this->_config = $config;
        $this->_modelsHandler = $modelsHandler;
        $this->handlerControllersFactory = $handlerControllersFactory;
        $this->_authSession = $authSession;
        $this->messageManager = $messageManager;
        $this->_objectManager = $objectManager;
        $this->_logger = $logger;
        $this->_eventFactory = $eventFactory;
        $this->_request = $request;
        $this->_remoteAddress = $remoteAddress;
        $this->changesFactory = $changesFactory;
        $this->_controllersHandler = $controllersHandler
            ?: $this->_objectManager->get(ControllersLoggingHandler::class);
    }

    /**
     * PreDispatch action handler
     *
     * @param string $fullActionName Full action name like 'adminhtml_catalog_product_edit'
     * @param string $actionName Action name like 'save', 'edit' etc.
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function initAction($fullActionName, $actionName)
    {
        $this->_actionName = $actionName;

        if (!$this->_initAction) {
            $this->_initAction = $fullActionName;
        }

        $this->_lastAction = $fullActionName;

        $this->_eventConfig = $this->_config->getEventByFullActionName($fullActionName);
        if (is_array($this->_eventConfig)) {
            $this->_skipNextAction = !$this->_config->isEventGroupLogged($this->_eventConfig['group_name']);
        }
        if ($this->_skipNextAction) {
            return $this;
        }

        /**
         * Skip view action after save. For example on 'save and continue' click.
         *
         * Some modules always reloading page after save. We pass comma-separated list
         * of actions into getSkipLoggingAction, it is necessary for such actions
         * like customer balance, when customer balance ajax tab loaded after
         * customer page.
         */
        $sessionValue = $this->_authSession->getSkipLoggingAction();
        if ($sessionValue) {
            if (is_array($sessionValue)) {
                $key = array_search($fullActionName, $sessionValue);
                if ($key !== false) {
                    unset($sessionValue[$key]);
                    $this->_authSession->setSkipLoggingAction($sessionValue);
                    $this->_skipNextAction = true;
                    return $this;
                }
            }
        }

        if (isset($this->_eventConfig['skip_on_back'])) {
            $addValue = $this->_eventConfig['skip_on_back'];
            if (!is_array($sessionValue) && $sessionValue) {
                $sessionValue = explode(',', $sessionValue);
            } elseif (!$sessionValue) {
                $sessionValue = [];
            }
            $merge = array_merge($addValue, $sessionValue);
            $this->_authSession->setSkipLoggingAction($merge);
        }
        return $this;
    }

    /**
     * Action model processing.
     *
     * Get difference between data & orig_data and store in the internal modelsHandler container.
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @param string $action
     * @return $this|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function modelActionAfter($model, $action)
    {
        if ($this->_skipNextAction) {
            return false;
        }

        if (false === $usedModels = $this->prepareUsedModels()) {
            return false;
        }

        $additionalData = $skipData = [];
        /**
         * Log event changes for each model
         */
        foreach ($usedModels as $className => $params) {
            /**
             * Add custom skip fields per expecetd model
             */
            if (isset($params['skip_data'])) {
                $skipData = array_unique($params['skip_data']);
            }

            /**
             * Add custom additional fields per expecetd model
             */
            if (isset($params['additional_data'])) {
                $additionalData = array_unique($params['additional_data']);
            }
            /**
             * Clean up additional data with skip data
             */
            $additionalData = array_diff($additionalData, $skipData);

            if (!$model instanceof $className) {
                continue;
            }

            $callback = sprintf('model%sAfter', ucfirst($action));
            $this->collectAdditionalData($model, $additionalData);
            $changes = $this->_modelsHandler->{$callback}($model, $this);

            /* $changes will not be an object in case of view action */
            if (!is_object($changes)) {
                return $this;
            }
            $changes->cleanupData($skipData);
            if ($changes->hasDifference()) {
                $changes->setSourceName($className);
                $changes->setSourceId($model->getId());
                $this->addEventChanges($changes);
            }
        }
        return $this;
    }

    /**
     * Post-dispatch action handler
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return $this|bool
     */
    public function logAction()
    {
        if (!$this->_initAction || !$this->_eventConfig) {
            return false;
        }

        if ($this->_actionName == 'denied') {
            $this->logDeniedAction();
            return $this;
        }

        if ($this->_skipNextAction) {
            return false;
        }

        $loggingEvent = $this->initLoggingEvent();
        $action = isset($this->_eventConfig['action']) ? $this->_eventConfig['action'] : '';
        $loggingEvent->setAction($action);
        $groupName = isset($this->_eventConfig['group_name']) ? $this->_eventConfig['group_name'] : '';
        $loggingEvent->setEventCode($groupName);

        try {
            if (!$this->callPostDispatchCallback($loggingEvent)) {
                return false;
            }

            /* Prepare additional info */
            if ($this->getCollectedAdditionalData()) {
                $loggingEvent->setAdditionalInfo($this->getCollectedAdditionalData());
            }
            $loggingEvent->save();
            $this->saveEventChanges($loggingEvent);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
        return $this;
    }

    /**
     * Initialize logging event
     *
     * @return Event
     */
    private function initLoggingEvent()
    {
        $username = null;
        $userId = null;
        if ($this->_authSession->isLoggedIn()) {
            $userId = $this->_authSession->getUser()->getId();
            $username = $this->_authSession->getUser()->getUsername();
        }
        $errors = $this->messageManager->getMessages()->getErrors();
        $closure = function (MessageInterface $message) {
            return $message->toString();
        };
        /** @var \Magento\Logging\Model\Event $loggingEvent */
        $loggingEvent = $this->_eventFactory->create()->setData(
            [
                'ip' => $this->_remoteAddress->getRemoteAddress(),
                'x_forwarded_ip' => $this->_request->getServer('HTTP_X_FORWARDED_FOR'),
                'user' => $username,
                'user_id' => $userId,
                'is_success' => empty($errors),
                'fullaction' => $this->_initAction,
                'error_message' => implode("\n", array_map($closure, $errors)),
            ]
        );
        return $loggingEvent;
    }

    /**
     * Call post dispatch callback
     *
     * @param Event $loggingEvent
     * @return $this|bool
     */
    private function callPostDispatchCallback($loggingEvent)
    {
        $handler = $this->_controllersHandler;
        $callback = 'postDispatchGeneric';

        if (isset($this->_eventConfig['post_dispatch'])) {
            $classPath = explode('::', $this->_eventConfig['post_dispatch']);
            if (count($classPath) == 2) {
                $handler = $this->_objectManager->get(str_replace('__', '/', $classPath[0]));
                $callback = $classPath[1];
            } else {
                $callback = $classPath[0];
            }
            if (!$handler || !$callback || !method_exists($handler, $callback)) {
                $this->_logger->critical(
                    new \Magento\Framework\Exception\LocalizedException(
                        __('Unknown callback function: %1::%2', $handler, $callback)
                    )
                );
            }
        }

        if (!$handler) {
            return false;
        }

        if (!$handler->{$callback}($this->_eventConfig, $loggingEvent, $this)) {
            return false;
        }
        return $this;
    }

    /**
     * Save event changes
     *
     * @param Event $loggingEvent
     * @return $this|bool
     */
    private function saveEventChanges($loggingEvent)
    {
        if (!$loggingEvent->getId()) {
            return false;
        }
        foreach ($this->_eventChanges as $changes) {
            if ($changes && ($changes->getOriginalData() || $changes->getResultData())) {
                $changes->setEventId($loggingEvent->getId());
                $changes->save();
            }
        }
        return $this;
    }

    /**
     * Log "denied" action
     *
     * @return $this|bool
     */
    public function logDeniedAction()
    {
        if ($this->_actionName != 'denied') {
            return false;
        }
        if (!$this->_eventConfig || !$this->_config->isEventGroupLogged($this->_eventConfig['group_name'])) {
            return $this;
        }
        $loggingEvent = $this->initLoggingEvent();
        $loggingEvent->setAction($this->_eventConfig['action']);
        $loggingEvent->setEventCode($this->_eventConfig['group_name']);
        $loggingEvent->setInfo(__('More permissions are needed to access this.'));
        $loggingEvent->setIsSuccess(0);
        $loggingEvent->save();
        return $this;
    }

    /**
     * Collect $model id
     *
     * @param AbstractModel $model
     * @return void
     */
    public function collectId($model)
    {
        $this->_collectedIds[get_class($model)][] = $model->getId();
    }

    /**
     * Collected ids getter
     *
     * @return array
     */
    public function getCollectedIds()
    {
        $ids = [];
        foreach ($this->_collectedIds as $className => $classIds) {
            $uniqueIds = array_unique($classIds);
            foreach ($uniqueIds as $uniqueId) {
                $ids[] = $uniqueId;
            }
            $this->_collectedIds[$className] = $uniqueIds;
        }
        return $ids;
    }

    /**
     * Collect $model additional attributes
     *
     * @param AbstractModel $model
     * @param array $attributes
     * @example
     * Array
     *     (
     *          [Magento\Sales\Model\Order] => Array
     *             (
     *                 [68] => Array
     *                     (
     *                         [increment_id] => 100000108,
     *                         [grand_total] => 422.01
     *                     )
     *                 [94] => Array
     *                     (
     *                         [increment_id] => 100000121,
     *                         [grand_total] => 492.77
     *                     )
     *              )
     *     )
     */
    public function collectAdditionalData($model, array $attributes)
    {
        $attributes = array_unique($attributes);
        if ($modelId = $model->getId()) {
            foreach ($attributes as $attribute) {
                $value = $model->getDataUsingMethod($attribute);
                if (!empty($value)) {
                    $this->_additionalData[get_class($model)][$modelId][$attribute] = $value;
                }
            }
        }
    }

    /**
     * Collected additional attributes getter
     *
     * @return array
     */
    public function getCollectedAdditionalData()
    {
        return $this->_additionalData;
    }

    /**
     * Add new event changes
     *
     * @param Changes $eventChange
     * @return $this
     */
    public function addEventChanges($eventChange)
    {
        $this->_eventChanges[] = $eventChange;
        return $this;
    }

    /**
     * Create event changes object
     *
     * @param string $name
     * @param mixed $original
     * @param mixed $result
     * @return Changes
     */
    public function createChanges($name, $original, $result)
    {
        $change = $this->changesFactory->create();
        $change->setSourceName($name)
            ->setOriginalData($original)
            ->setResultData($result);
        return $change;
    }

    /**
     * Prepare models
     *
     * @return array | false
     */
    private function prepareUsedModels()
    {
        /**
         * These models used when we merge action models with action group models
         */
        $groupExpectedModels = null;
        if ($this->_eventConfig) {
            $eventGroupNode = $this->_config->getEventGroupConfig($this->_eventConfig['group_name']);
            if (isset($eventGroupNode['expected_models'])) {
                $groupExpectedModels = $eventGroupNode['expected_models'];
            }
        }

        /**
         * Exact models in exactly action node
         */
        $expectedModels = $this->_eventConfig['expected_models'] ?? [];

        /** @phpstan-ignore-next-line */
        if (empty($expectedModels) && empty($groupExpectedModels)) {
            return false;
        }

        if (empty($expectedModels)) {
            return $groupExpectedModels;
        }

        if (isset($expectedModels['@']['extends']) && $expectedModels['@']['extends'] === 'merge') {
            return array_replace_recursive($groupExpectedModels, $expectedModels);
        }
        return $expectedModels;
    }
}
