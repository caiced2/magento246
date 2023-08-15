<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute;
use Magento\Framework\Serialize\Serializer\FormData;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Helper\Customer as HelperCustomer;
use Magento\CustomerCustomAttributes\Helper\Data as HelperData;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Registry;
use Magento\Store\Model\WebsiteFactory;

/**
 * Customer custom attribute save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Attribute implements HttpPostActionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperCustomer
     */
    protected $helperCustomer;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var mixed OptionsDataSerializer
     */
    private $formDataSerializer;

    /**
     * @var array
     */
    private $deniedAttributes = [];

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Config $eavConfig
     * @param AttributeFactory $attrFactory
     * @param SetFactory $attrSetFactory
     * @param WebsiteFactory $websiteFactory
     * @param HelperData $helperData
     * @param HelperCustomer $helperCustomer
     * @param FilterManager $filterManager
     * @param FormData|null $formDataSerializer
     * @param array $deniedAttributes
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Config $eavConfig,
        AttributeFactory $attrFactory,
        SetFactory $attrSetFactory,
        WebsiteFactory $websiteFactory,
        HelperData $helperData,
        HelperCustomer $helperCustomer,
        FilterManager $filterManager,
        FormData $formDataSerializer = null,
        array $deniedAttributes = []
    ) {
        $this->helperData = $helperData;
        $this->helperCustomer = $helperCustomer;
        $this->filterManager = $filterManager;
        $this->formDataSerializer = $formDataSerializer
            ?: ObjectManager::getInstance()->get(FormData::class);
        $this->deniedAttributes = $deniedAttributes;
        parent::__construct(
            $context,
            $coreRegistry,
            $eavConfig,
            $attrFactory,
            $attrSetFactory,
            $websiteFactory
        );
    }

    /**
     * Save attribute action
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $optionData = $this->formDataSerializer
                ->unserialize($this->getRequest()->getParam('serialized_options', '[]'));
        } catch (\InvalidArgumentException $e) {
            $message = __("The attribute couldn't be saved due to an error. Verify your information and try again. "
                . "If the error persists, please try again later.");
            $this->messageManager->addErrorMessage($message);
            return $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
        }

        $data = $this->getRequest()->getPostValue();
        $data = array_replace_recursive(
            $data,
            $optionData
        );

        if ($this->getRequest()->isPost() && $data) {
            /* @var $attributeObject \Magento\Customer\Model\Attribute */
            $attributeObject = $this->_initAttribute();

            //filtering
            try {
                $data = $this->helperCustomer->filterPostData($data);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                if (isset($data['attribute_id'])) {
                    $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                } else {
                    $resultRedirect->setPath('adminhtml/*/new', ['_current' => true]);
                }
                return $resultRedirect;
            }

            $attributeId = $this->getRequest()->getParam('attribute_id');
            if ($attributeId) {
                $attributeObject->load($attributeId);
                if ($attributeObject->getEntityTypeId() != $this->_getEntityType()->getId()
                    || array_key_exists('backend_model', $data)
                ) {
                    $this->messageManager->addError(__('You cannot edit this attribute.'));
                    $this->_getSession()->setAttributeData($data);
                    $resultRedirect->setPath('adminhtml/*/');

                    return $resultRedirect;
                }

                $data['attribute_code'] = $attributeObject->getAttributeCode();
                $data['is_user_defined'] = $attributeObject->getIsUserDefined();
                $data['frontend_input'] = $attributeObject->getFrontendInput();
                $data['is_user_defined'] = $attributeObject->getIsUserDefined();
                $data['is_system'] = $attributeObject->getIsSystem();
            } else {
                $data['backend_model'] = $this->helperData->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );
                $data['source_model'] = $this->helperData->getAttributeSourceModelByInputType($data['frontend_input']);
                $data['backend_type'] = $this->helperData->getAttributeBackendTypeByInputType($data['frontend_input']);
                $data['is_user_defined'] = 1;
                $data['is_system'] = 0;

                // add set and group info
                $data['attribute_set_id'] = $this->_getEntityType()->getDefaultAttributeSetId();
                /** @var $attrSet \Magento\Eav\Model\Entity\Attribute\Set */
                $attrSet = $this->_attrSetFactory->create();
                $data['attribute_group_id'] = $attrSet->getDefaultGroupId($data['attribute_set_id']);
            }

            //Always assign the default form
            if (!isset($data['used_in_forms_disabled'])) {
                $defaultFormName = 'adminhtml_customer';
                if (empty($data['used_in_forms'])) {
                    $data['used_in_forms'] = [$defaultFormName];
                } elseif (is_array($data['used_in_forms'])) {
                    $data['used_in_forms'][] = $defaultFormName;
                }
            }

            $defaultValueField = $this->helperData->getAttributeDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $scopeKeyPrefix = $this->getRequest()->getParam('website') ? 'scope_' : '';
                $defaultValue = $this->getRequest()->getParam($scopeKeyPrefix . $defaultValueField);
                $data[$scopeKeyPrefix . 'default_value'] = $defaultValue
                    ? $this->filterManager->stripTags($defaultValue) : null;
            }

            $data['entity_type_id'] = $this->_getEntityType()->getId();
            $data['validate_rules'] = $this->helperData->getAttributeValidateRules($data['frontend_input'], $data);

            $validateRulesErrors = $this->helperData->checkValidateRules(
                $data['frontend_input'],
                $data['validate_rules']
            );
            if (count($validateRulesErrors)) {
                foreach ($validateRulesErrors as $message) {
                    $this->messageManager->addError($message);
                }
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            }

            $attributeObject->addData($data);

            /**
             * Check "Use Default Value" checkboxes values
             */
            $useDefaults = $this->getRequest()->getPost('use_default');
            if ($useDefaults) {
                foreach ($useDefaults as $key) {
                    if (!is_array($key) && !in_array($key, $this->deniedAttributes)) {
                        $attributeObject->setData($key, null);
                    }
                }
            }

            try {
                $this->_eventManager->dispatch(
                    'magento_customercustomattributes_attribute_before_save',
                    ['attribute' => $attributeObject]
                );
                $attributeObject->save();
                $this->_eventManager->dispatch(
                    'magento_customercustomattributes_attribute_save',
                    ['attribute' => $attributeObject]
                );

                $this->messageManager->addSuccess(__('You saved the customer attribute.'));
                $this->_getSession()->setAttributeData(false);
                if ($this->getRequest()->getParam('back', false)) {
                    $resultRedirect->setPath(
                        'adminhtml/*/edit',
                        ['attribute_id' => $attributeObject->getId(), '_current' => true]
                    );
                } else {
                    $resultRedirect->setPath('adminhtml/*/');
                }
                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $data['frontend_label'] = $data['frontend_label'][0] ?? $data['frontend_label'];
                $this->_getSession()->setAttributeData($data);
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t save the customer address attribute right now.'));
                $this->_getSession()->setAttributeData($data);
                $resultRedirect->setPath('adminhtml/*/edit', ['_current' => true]);
                return $resultRedirect;
            }
        }
        $resultRedirect->setPath('adminhtml/*/');
        return $resultRedirect;
    }
}
