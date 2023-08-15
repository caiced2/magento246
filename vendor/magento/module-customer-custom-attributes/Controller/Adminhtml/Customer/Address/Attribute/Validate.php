<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\CompositeValidator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

/**
 * Validate controller.
 */
class Validate extends Attribute implements HttpPostActionInterface
{
    /**
     * @var NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @var CompositeValidator
     */
    private $compositeValidator;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Customer\Model\AttributeFactory $attrFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $attrSetFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param NotProtectedExtension|null $extensionValidator
     * @param CompositeValidator|null $compositeValidator
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Model\AttributeFactory $attrFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attrSetFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        NotProtectedExtension $extensionValidator = null,
        CompositeValidator $compositeValidator = null
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $eavConfig,
            $attrFactory,
            $attrSetFactory,
            $websiteFactory
        );
        $this->extensionValidator = $extensionValidator
            ?: ObjectManager::getInstance()->get(NotProtectedExtension::class);
        $this->compositeValidator = $compositeValidator ?: ObjectManager::getInstance()->get(CompositeValidator::class);
    }

    /**
     * Validate attribute action.
     *
     * @return void
     */
    public function execute()
    {
        $response = new DataObject();
        $response->setError(false);
        $data = $this->getRequest()->getPostValue();
        $data['entity_type_id'] = $this->_getEntityType()->getId();
        $attributeObject = $this->_initAttribute();
        $attributeObject->addData($data);

        try {
            $this->compositeValidator->validate($attributeObject);
        } catch (LocalizedException $e) {
            $this->setMessageToResponse($response, $e->getMessage());
            $response->setError(true);
        }

        $this->getResponse()->setBody($response->toJson());
    }

    /**
     * Set message to response object.
     *
     * @param DataObject $response
     * @param string $message
     * @return DataObject
     */
    private function setMessageToResponse(DataObject $response, string $message): DataObject
    {
        $defaultMessageKey = 'message';
        $messageKey = $this->getRequest()->getParam('message_key', $defaultMessageKey);

        return $response->setData($messageKey, $message);
    }
}
