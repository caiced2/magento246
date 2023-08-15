<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Sku;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Admin Checkout main form container
 *
 * @method string getListType()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku setListType(string $type)
 * @method string getDataContainerId()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku setDataContainerId(string $id)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractSku extends \Magento\Backend\Block\Template
{
    /**
     * List type of current block
     */
    const LIST_TYPE = 'add_by_sku';

    /**
     * @var string
     */
    protected $_template = 'sku/add.phtml';

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = [],
        SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        parent::__construct($context, $data);
    }

    /**
     * Initialize SKU container
     *
     * @return void
     */
    protected function _construct()
    {
        // Used by JS to tell accordions from each other
        $this->setId('sku');
        /* @see \Magento\AdvancedCheckout\Controller\Adminhtml\Index::_getListItemInfo() */
        $this->setListType(self::LIST_TYPE);
        $this->setDataContainerId('sku_container');
    }

    /**
     * Define ADD and DEL buttons
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        //Delete button will be copied for each row so we need a listener that will work for duplicates.
        $deleteButtonId = $this->mathRandom->getRandomString('8');
        $deleteButtonClass = 'admin-checkout-sku-delete-button-' . $deleteButtonId;
        $deleteFunctionName = 'skuDeleteButtonListener' . $deleteButtonId;
        $deleteActionScript = <<<SCRIPT
            if (typeof($deleteFunctionName) == "undefined") {
                $deleteFunctionName = function (event) {
 		            addBySku.del(event.target);
 	 		    };
 	 	 	    require(['jquery'], function($){
 	 	 	        $("body").on("click", ".$deleteButtonClass", $deleteFunctionName);
 	 	 	    });
 	 	 	}
SCRIPT;
        $this->addChild(
            'deleteButton',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => '',
                'class' => 'action-delete ' . $deleteButtonClass,
                'before_html' => $this->secureRenderer->renderTag(
                    'script',
                    ['type' => 'text/javascript'],
                    $deleteActionScript,
                    false
                ),
            ]
        );

        $this->addChild(
            'addButton',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => 'Add another', 'onclick' => 'addBySku.add()', 'class' => 'add']
        );

        return $this;
    }

    /**
     * HTML of "+" button, which adds new field for SKU and qty
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('addButton');
    }

    /**
     * HTML of "x" button, which removes field with SKU and qty
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('deleteButton');
    }

    /**
     * Returns URL to which CSV file should be submitted
     *
     * @abstract
     * @return string
     * @codeCoverageIgnore
     */
    abstract public function getFileUploadUrl();

    /**
     * Configuration data for AddBySku instance
     *
     * @return string
     */
    public function getAddBySkuDataJson()
    {
        $data = [
            'dataContainerId' => $this->getDataContainerId(),
            'deleteButtonHtml' => $this->getDeleteButtonHtml(),
            'fileUploaded' => \Magento\AdvancedCheckout\Helper\Data::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG,
            // All functions requiring listType affects error grid only
            'listType' => \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\AbstractErrors::LIST_TYPE,
            'errorGridId' => $this->getErrorGridId(),
            'fileFieldName' => \Magento\AdvancedCheckout\Model\Import::FIELD_NAME_SOURCE_FILE,
            'fileUploadUrl' => $this->getFileUploadUrl(),
        ];

        $json = $this->_jsonEncoder->encode($data);
        return $json;
    }

    /**
     * JavaScript instance of AdminOrder or AdminCheckout
     *
     * @abstract
     * @return string
     */
    abstract public function getJsOrderObject();

    /**
     * HTML ID of error grid container
     *
     * @abstract
     * @return string
     */
    abstract public function getErrorGridId();

    /**
     * Retrieve context specific JavaScript
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getContextSpecificJs()
    {
        return '';
    }

    /**
     * Retrieve additional JavaScript
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAdditionalJavascript()
    {
        return '';
    }
}
