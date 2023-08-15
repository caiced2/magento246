<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardImportExport\Model\Import\Product\Type;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class GiftCard for Import entity of GiftCard product type
 */
class GiftCard extends \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
{
    /**
     * Column names
     */
    public const GIFTCARD_TYPE_COLUMN = 'giftcard_type';

    public const GIFTCARD_AMOUNT_COLUMN = 'giftcard_amount';

    public const ALLOW_OPEN_AMOUNT_COLUMN = 'allow_open_amount';

    public const OPEN_AMOUNT_MIN_COLUMN = 'open_amount_min';

    public const OPEN_AMOUNT_MAX_COLUMN = 'open_amount_max';

    public const IS_REDEEMABLE_COLUMN = 'is_redeemable';

    public const LIFETIME_COLUMN = 'lifetime';

    public const ALLOW_MESSAGE_COLUMN = 'allow_message';

    public const EMAIL_TEMPLATE_COLUMN = 'email_template';

    public const USE_CUSTOM_SETTINGS_VALUE = 0;

    public const USE_CONFIG_SETTINGS_VALUE = 1;

    public const GIFTCARD_AMOUNT_TABLE = 'magento_giftcard_amount';

    public const GIFTCARD_PREFIX = 'giftcard_';

    public const USE_CONFIG_PREFIX = 'use_config_';

    public const AMOUNT_ATTRIBUTE_NAME = 'giftcard_amounts';

    public const GIFTCARD_TYPE_VIRTUAL = 'virtual';

    public const GIFTCARD_DEFAULT_TEMPLATE_NAME = 'Gift Card(s) Purchase (Default)';

    /**
     * Default website id for gift card import/export module
     */
    public const DEFAULT_WEBSITE_ID = 0;

    /**
     * Error codes if amount not found.
     */
    public const ERROR_AMOUNTS_ATTRIBUTE_ID_NOT_FOUND = 'amountsAttributeIdNotFound';

    public const ERROR_AMOUNT_NOT_FOUND = 'amountNotFound';

    /**
     * Giftcard attributes
     *
     * @var array
     */
    protected $giftcardAmountFields = [
        self::AMOUNT_ATTRIBUTE_NAME,
        self::ALLOW_OPEN_AMOUNT_COLUMN,
        self::OPEN_AMOUNT_MIN_COLUMN,
        self::OPEN_AMOUNT_MAX_COLUMN,
    ];

    /**
     * Giftcard attributes with use config settings attribute
     *
     * @var array
     */
    protected $giftcardMessagingSettingsFields = [
        self::IS_REDEEMABLE_COLUMN,
        self::LIFETIME_COLUMN,
        self::EMAIL_TEMPLATE_COLUMN,
        self::ALLOW_MESSAGE_COLUMN,
    ];

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_AMOUNTS_ATTRIBUTE_ID_NOT_FOUND => 'Attribute id for giftcard amounts not found',
        self::ERROR_AMOUNT_NOT_FOUND => 'Amount for giftcard is not specified',
    ];

    /**
     * Cache for amounts
     *
     * @var array
     */
    protected $amountsCache = [];

    /**
     * Giftcard amounts attribute ID
     *
     * @var int
     */
    protected $giftcardAmountAttributeId;

    /**
     * Email config options factory
     *
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    protected $templatesFactory;

    /**
     * Email templates label to id array
     *
     * @var $array
     * @deprecated 100.3.1
     * @see we don't recommend this approach anymore
     */
    protected $emailTemplatesLabelToId;

    /**
     * @var Product\StoreResolver
     */
    private $storeResolver;

    /**
     * @var \Magento\Config\Model\Config\Source\Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $params
     * @param \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver
     * @param \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateFactory
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\Config\Model\Config\Source\Email\TemplateFactory $templateFactory,
        MetadataPool $metadataPool = null
    ) {
        parent::__construct($attrSetColFac, $prodAttrColFac, $resource, $params, $metadataPool);
        $this->storeResolver = $storeResolver;
        $this->templateFactory = $templateFactory;
    }

    /**
     * Save Giftcard data
     *
     * @return $this
     */
    public function saveData()
    {
        while ($bunch = $this->_entityModel->getNextBunch()) {
            $newSku = $this->_entityModel->getNewSku();
            foreach ($bunch as $rowNum => $rowData) {
                $productData = $newSku[strtolower($rowData[Product::COL_SKU])];
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)
                    || $this->_type != $productData['type_id']) {
                    continue;
                }
                $this->parseAmounts($rowData, $productData[$this->getProductEntityLinkField()]);
            }
            if (!empty($this->amountsCache)) {
                $this->populateExistingOptions()
                     ->insertAmounts()
                     ->clearAmountsCache();
            }
        }

        return $this;
    }

    /**
     * Validate row attributes. Pass VALID row data ONLY as argument.
     *
     * @param array $rowData
     * @param int $rowNum
     * @param bool $isNewProduct Optional
     *
     * @return bool
     */
    public function isRowValid(array $rowData, $rowNum, $isNewProduct = true)
    {
        $hasError = !parent::isRowValid($rowData, $rowNum, $isNewProduct);
        if ($this->getGiftcardAmountsAttributeId() === null) {
            $this->_entityModel->addRowError(self::ERROR_AMOUNTS_ATTRIBUTE_ID_NOT_FOUND, $rowNum);
            $hasError = true;
        }
        $rowScope = $this->_entityModel->getRowScope($rowData);
        $attrCode = 'giftcard_amount';
        if (!$this->isAmountValid($rowData)) {
            if (Product::SCOPE_DEFAULT === $rowScope
                && ($isNewProduct || array_key_exists($attrCode, $rowData))
            ) {
                $this->_entityModel->addRowError(
                    self::ERROR_AMOUNT_NOT_FOUND,
                    $rowNum,
                    $attrCode
                );
                $hasError = true;
            }
        }

        return !$hasError;
    }

    /**
     * Prepare attributes with default value for save.
     *
     * @param array $rowData
     * @param bool $withDefaultValue
     * @return array
     */
    public function prepareAttributesWithDefaultValueForSave(array $rowData, $withDefaultValue = true)
    {
        $resultAttributes = parent::prepareAttributesWithDefaultValueForSave($rowData, $withDefaultValue);
        $resultAttributes = array_merge(
            $resultAttributes,
            $this->retrieveGiftcardAttributes($rowData, $this->giftcardAmountFields),
            $this->retrieveGiftcardAttributes($rowData, $this->giftcardMessagingSettingsFields, true),
            $this->setWeightVirtualGiftCard($rowData),
            $this->retrieveEmailTemplateValue($rowData)
        );

        return $resultAttributes;
    }

    /**
     * Parse giftcard amounts values.
     *
     * @param array $rowData
     * @param int $entityId
     * @return $this
     */
    protected function parseAmounts($rowData, $entityId)
    {
        if (isset($rowData[self::GIFTCARD_AMOUNT_COLUMN])) {
            $amounts = explode(
                $this->_entityModel->getMultipleValueSeparator(),
                trim($rowData[self::GIFTCARD_AMOUNT_COLUMN])
            );
            $amountData['website_id'] = (isset($rowData['website_code']))
                ? $this->storeResolver->getWebsiteCodeToId($rowData['website_code'])
                : self::DEFAULT_WEBSITE_ID;
            $amountData['attribute_id'] = $this->getGiftcardAmountsAttributeId();
            $amountData[$this->getProductEntityLinkField()] = $entityId;
            foreach ($amounts as $amount) {
                $amountData['value'] = $amount;
                $this->amountsCache[] = $amountData;
            }
        }

        return $this;
    }

    /**
     * Insert amounts from bunch
     *
     * @return $this
     */
    protected function insertAmounts()
    {
        $amountTable = $this->_resource->getTableName(self::GIFTCARD_AMOUNT_TABLE);
        $this->connection->insertOnDuplicate($amountTable, $this->amountsCache);

        return $this;
    }

    /**
     * Clear cached amount values
     *
     * @return $this
     */
    protected function clearAmountsCache()
    {
        $this->amountsCache = [];

        return $this;
    }

    /**
     * Check if giftcard amounts attribute id exists
     *
     * @return int
     */
    protected function getGiftcardAmountsAttributeId()
    {
        if (!$this->giftcardAmountAttributeId) {
            $this->giftcardAmountAttributeId
                = isset($this->retrieveAttributeFromCache(self::AMOUNT_ATTRIBUTE_NAME)['id'])
                ? $this->retrieveAttributeFromCache(self::AMOUNT_ATTRIBUTE_NAME)['id']
                : null;
        }

        return $this->giftcardAmountAttributeId;
    }

    /**
     * Check if at least one of amounts exists
     *
     * @param array $rowData
     * @return bool
     */
    protected function isAmountValid($rowData)
    {
        $isValid = true;
        $giftcardAmountColumn = self::GIFTCARD_AMOUNT_COLUMN;
        $giftcardAllowOpenAmountColumn = self::GIFTCARD_PREFIX . self::ALLOW_OPEN_AMOUNT_COLUMN;
        if (empty($rowData[$giftcardAmountColumn]) && empty($rowData[$giftcardAllowOpenAmountColumn])) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Retrieve values for giftcard attributes
     *
     * @param array $rowData
     * @param array $columns
     * @param bool $withUseConfigValue
     * @return array
     */
    protected function retrieveGiftcardAttributes($rowData, $columns, $withUseConfigValue = false)
    {
        $resultAttributes = [];
        foreach ($columns as $columnName) {
            $fieldName = self::GIFTCARD_PREFIX . $columnName;
            $useConfigFieldName = self::USE_CONFIG_PREFIX . $columnName;
            if (isset($rowData[$fieldName])) {
                $resultAttributes[$columnName] = $rowData[$fieldName];
            }
            if ($withUseConfigValue) {
                $resultAttributes[$useConfigFieldName] = !isset($rowData[$fieldName])
                    ? self::USE_CONFIG_SETTINGS_VALUE
                    : self::USE_CUSTOM_SETTINGS_VALUE;
            }
        }

        return $resultAttributes;
    }

    /**
     * Set weight null for virtual Giftcard.
     *
     * @param array $rowData
     * @return array
     */
    protected function setWeightVirtualGiftCard(array $rowData)
    {
        $result = [];
        if (isset($rowData[self::GIFTCARD_TYPE_COLUMN])
            && $rowData[self::GIFTCARD_TYPE_COLUMN] === self::GIFTCARD_TYPE_VIRTUAL
        ) {
            $result['weight'] = null;
        }

        return $result;
    }

    /**
     * Return email template value.
     *
     * @param array $rowData
     * @return array
     */
    private function retrieveEmailTemplateValue(array $rowData): array
    {
        $result = [];
        $fieldName = self::GIFTCARD_PREFIX . self::EMAIL_TEMPLATE_COLUMN;
        if (isset($rowData[$fieldName]) && $this->isTemplateExist($rowData)) {
            $result[self::EMAIL_TEMPLATE_COLUMN] = $rowData[$fieldName];
        } else {
            $result[self::EMAIL_TEMPLATE_COLUMN] = $this->getDefaultTemplate();
        }

        return $result;
    }

    /**
     * Check that Template exists.
     *
     * @param array $rowData
     * @return bool
     */
    private function isTemplateExist(array $rowData): bool
    {
        $fieldName = self::GIFTCARD_PREFIX . self::EMAIL_TEMPLATE_COLUMN;
        $isTemplateExist = false;
        $template = $this->templateFactory->create();
        $template->setPath(\Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_TEMPLATE);
        foreach ($template->toOptionArray() as $template) {
            if ($template['value'] === $rowData[$fieldName]) {
                $isTemplateExist = true;
                break;
            }
        }

        return $isTemplateExist;
    }

    /**
     * Get default Template.
     *
     * @return string
     */
    private function getDefaultTemplate(): string
    {
        $template = $this->templateFactory->create();
        $template->setPath(\Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_TEMPLATE);
        $defaultTemplate = null;
        foreach ($template->toOptionArray() as $template) {
            $defaultTemplate = $template['value'];
            break;
        }

        return $defaultTemplate;
    }

    /**
     * Return email template select options.
     *
     * @param array $rowData
     * @return array
     * @deprecated 100.3.1 The method logic is wrong.
     * @see retrieveEmailTemplateValue()
     */
    public function retrieveEmailTemplateId($rowData)
    {
        $result = [];
        $fieldName = self::GIFTCARD_PREFIX . self::EMAIL_TEMPLATE_COLUMN;
        if (isset($rowData[$fieldName]) && $rowData[$fieldName] != self::GIFTCARD_DEFAULT_TEMPLATE_NAME) {
            $result[self::EMAIL_TEMPLATE_COLUMN] = $this->retrieveEmailTemplateIdByLabel($rowData[$fieldName]);
        }
        return $result;
    }

    /**
     * Retrieve email template id by label.
     *
     * @param string $label
     * @return int|null
     * @deprecated 100.3.1 The method logic is wrong.
     * @see getDefaultTemplate()
     */
    protected function retrieveEmailTemplateIdByLabel($label)
    {
        if (!$this->emailTemplatesLabelToId) {
            $this->getEmailTemplatesLabelToId();
        }
        return isset($this->emailTemplatesLabelToId[$label]) ? $this->emailTemplatesLabelToId[$label] : null;
    }

    /**
     * Get email templates label to id array.
     *
     * @deprecated 100.3.1 The method logic is wrong.
     * @see getDefaultTemplate()
     */
    protected function getEmailTemplatesLabelToId()
    {
        if (!$this->emailTemplatesLabelToId) {
            $template = $this->templateFactory->create();
            $template->setPath(\Magento\GiftCard\Model\Giftcard::XML_PATH_EMAIL_TEMPLATE);
            foreach ($template->toOptionArray() as $template) {
                if (!is_string($template['label'])) {
                    $label = $template['label'];
                    $template['label'] = $label->render();
                }
                $this->emailTemplatesLabelToId[$template['label']] = $template['value'];
            }
        }
        return $this->emailTemplatesLabelToId;
    }
    /**
     * Populates existing options.
     *
     * @return $this
     */
    protected function populateExistingOptions():object
    {
        $result = array_filter($this->amountsCache, function ($var) {
            $found = false;
            array_walk_recursive($var, function ($item, $key) use (&$found) {
                $found = $found || $key == "row_id";
            });
            return $found;
        });
        if (!empty($result)) {
            $amountTable = $this->_resource->getTableName(self::GIFTCARD_AMOUNT_TABLE);
            $select = $this->connection->select()->from(
                ['ga' => $amountTable],
                ['value_id','website_id','attribute_id','row_id','value']
            );
            foreach ($this->amountsCache as $item) {
                $select->orWhere('website_id = ' . $item['website_id'] . ' AND attribute_id = ' . $item['attribute_id']
                    . ' AND row_id = ' . $item['row_id'] . ' AND value = ?', $item['value']);
            }
            $existingOptions = $this->connection->fetchAll($select);
            if (!empty($existingOptions)) {
                $this->updateAmountsCache($existingOptions);
            }
        }
        return $this;
    }

    /**
     * Update amount cache.
     *
     * @param array $existingOptions
     * @return void
     */
    private function updateAmountsCache($existingOptions):void
    {
        foreach ($this->amountsCache as $key => $option) {
            $this->amountsCache[$key]['value_id'] = null;
            foreach ($existingOptions as $cacheOption) {
                if (($option['website_id'] == $cacheOption['website_id'])
                            && ($option['attribute_id'] == $cacheOption['attribute_id'])
                            && ($option['row_id'] == $cacheOption['row_id'])
                            && ($option['value'] == $cacheOption['value'])) {
                    $this->amountsCache[$key]['value_id'] = $cacheOption['value_id'];
                }
            }
        }
    }
}
