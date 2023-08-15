<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Validate controller
 */
class Validate extends \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute implements HttpPostActionInterface
{
    /**
     * @var \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension|null $extensionValidator
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $extensionValidator = null
    ) {
        parent::__construct($context, $coreRegistry, $websiteFactory);
        $this->extensionValidator = $extensionValidator
            ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\MediaStorage\Model\File\Validator\NotProtectedExtension::class);
    }

    /**
     * Validate attribute action
     *
     * @return void
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);
        $attributeId = $this->getRequest()->getParam('attribute_id');
        if (!$attributeId) {
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            $attributeObject = $this->_initAttribute()->loadByCode(
                $this->_getEntityType()->getId(),
                $attributeCode
            )->setCanManageOptionLabels(
                true
            );
            if ($attributeObject->getId()) {
                $this->messageManager->addError(__('An attribute with the same code already exists.'));

                $this->_view->getLayout()->initMessages();
                $response->setError(true);
                $response->setHtmlMessage($this->_view->getLayout()->getMessagesBlock()->getGroupedHtml());
            }
        }
        if ($this->getRequest()->getParam('frontend_input') === 'file') {
            $fileExtensions = explode(',', $this->getRequest()->getParam('file_extensions'));
            $isForbiddenExtensionsExists = false;

            foreach ($fileExtensions as $fileExtension) {
                if (!$this->extensionValidator->isValid($fileExtension)) {
                    $isForbiddenExtensionsExists = true;
                }
            }

            if ($isForbiddenExtensionsExists) {
                $response->setError(true);
                $response->setMessage(__('Please correct the value for file extensions.'));
            }
        }
        $this->getResponse()->representJson($response->toJson());
    }
}
