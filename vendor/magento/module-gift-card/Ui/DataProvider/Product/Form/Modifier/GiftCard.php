<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Config\Model\Config\Source\Email\TemplateFactory as EmailTemplateFactory;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardProductType;
use Magento\Directory\Model\Currency;
use Magento\GiftCard\Model\Giftcard as GiftCardModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\CurrencyInterface;

/**
 * Data provider for Gift Cards
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCard extends AbstractModifier
{
    const GROUP_GIFTCARD = 'giftcard-information';
    const CONTAINER_OPEN_AMOUNT_RANGE = 'open_amount_range';
    const FIELD_CONFIG_PREFIX = 'use_config_';
    const FIELD_GIFTCARD_TYPE = 'giftcard_type';
    const FIELD_GIFTCARD_AMOUNTS = 'giftcard_amounts';
    const FIELD_WEBSITE_ID = 'website_id';
    const FIELD_VALUE = 'value';
    const FIELD_DELETE = 'delete';
    const FIELD_ALLOW_OPEN_AMOUNT = 'allow_open_amount';
    const FIELD_OPEN_AMOUNT_MIN = 'open_amount_min';
    const FIELD_OPEN_AMOUNT_MAX = 'open_amount_max';
    const FIELD_IS_REDEEMABLE = 'is_redeemable';
    const FIELD_LIFETIME = 'lifetime';
    const FIELD_ALLOW_MESSAGE = 'allow_message';
    const FIELD_EMAIL_TEMPLATE = 'email_template';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    /**
     * @var \Magento\Config\Model\Config\Source\Email\TemplateFactory
     */
    protected $emailTemplateFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var array
     */
    protected $giftCardPanelData = [];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryHelper $directoryHelper
     * @param EmailTemplateFactory $emailTemplateFactory
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $currency
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig,
        DirectoryHelper $directoryHelper,
        EmailTemplateFactory $emailTemplateFactory,
        StoreManagerInterface $storeManager,
        CurrencyInterface $currency = null
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
        $this->directoryHelper = $directoryHelper;
        $this->emailTemplateFactory = $emailTemplateFactory;
        $this->storeManager = $storeManager;
        $this->currency = $currency ?: ObjectManager::getInstance()->get(CurrencyInterface::class);
    }

    /**
     * Add gift card info to data array {@inheritdoc}
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $dataPath = $product->getId() . '/' . static::DATA_SOURCE_DEFAULT;

        if ($product->getTypeId() == GiftCardProductType::TYPE_GIFTCARD) {
            $data = $this->arrayManager->merge(
                $dataPath,
                $data,
                array_replace_recursive(
                    [
                        static::FIELD_GIFTCARD_AMOUNTS => $this->formatAmounts(
                            $this->arrayManager->get(
                                $dataPath . '/' . static::FIELD_GIFTCARD_AMOUNTS,
                                $data,
                                []
                            )
                        )
                    ],
                    $this->getGiftCardPanelData()
                )
            );
        }

        return $data;
    }

    /**
     * Add gift card info to meta array {@inheritdoc}
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        if ($this->locator->getProduct()->getTypeId() === GiftCardProductType::TYPE_GIFTCARD) {
            $this->customizeAmountsField();
            $this->customizeOpenAmountFields();
            $this->customizeWeightField();
            $this->removeHasWeightField();
            $this->createGiftCardPanel();
        }

        return $this->meta;
    }

    /**
     * Customize Amounts field
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function customizeAmountsField()
    {
        $elementPath = $this->arrayManager->findPath(static::FIELD_GIFTCARD_AMOUNTS, $this->meta, null, 'children');
        $globalScope = $this->arrayManager->get($elementPath . '/globalScope', $this->meta);
        $containerPath = $this->arrayManager->slicePath($elementPath, 0, -2);
        $fieldsetPath = $this->arrayManager->slicePath($elementPath, 0, -4);
        $containerConfigPath = $containerPath . static::META_CONFIG_PATH;

        $this->meta = $this->arrayManager->replace(
            $elementPath,
            $this->meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $this->arrayManager->get($containerConfigPath . '/label', $this->meta),
                            'addButtonLabel' => __('Add Amount'),
                            'componentType' => 'dynamicRows',
                            'itemTemplate' => 'record',
                            'renderDefaultRecord' => false,
                            'renderColumnsHeader' => false,
                            'additionalClasses' => 'admin__field-amount ',
                            'dataScope' => '',
                            'dndConfig' => [
                                'enabled' => false
                            ],
                            'sortOrder' => $this->arrayManager->get($containerConfigPath . '/sortOrder', $this->meta),
                        ],
                    ],
                ],
                'children' => [
                    'record' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => Container::NAME,
                                    'isTemplate' => true,
                                    'is_collection' => true,
                                    'component' => 'Magento_Ui/js/lib/core/collection',
                                    'dataScope' => '',
                                ],
                            ],
                        ],
                        'children' => [
                            static::FIELD_VALUE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'label' => __('Amount'),
                                            'formElement' => Input::NAME,
                                            'componentType' => Field::NAME,
                                            'dataType' => 'number',
                                            'dataScope' => static::FIELD_VALUE,
                                            'addbefore' => $this->getCurrencySymbol(),
                                            'validation' => [
                                                'required-entry' => true,
                                                'validate-number' => true
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                            static::FIELD_WEBSITE_ID => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'label' => '',
                                            'formElement' => Select::NAME,
                                            'componentType' => Field::NAME,
                                            'dataType' => 'text',
                                            'dataScope' => static::FIELD_WEBSITE_ID,
                                            'options' => $this->getWebsites($globalScope),
                                            'visible' => !$this->storeManager->hasSingleStore()
                                        ],
                                    ],
                                ],
                            ],
                            'actionDelete' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'label' => '',
                                            'fit' => true,
                                            'componentType' => 'actionDelete',
                                            'dataType' => 'text',
                                            'dataScope' => static::FIELD_DELETE
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->meta = $this->arrayManager->set(
            $fieldsetPath . '/children/' . static::FIELD_GIFTCARD_AMOUNTS,
            $this->meta,
            $this->arrayManager->get($elementPath, $this->meta)
        );
        $this->meta = $this->arrayManager->remove($containerPath, $this->meta);
    }

    /**
     * Customize fields related to Open Amount settings
     *
     * @return $this
     */
    protected function customizeOpenAmountFields()
    {
        $openAmountPath = $this->arrayManager->findPath(static::FIELD_ALLOW_OPEN_AMOUNT, $this->meta, null, 'children')
            . static::META_CONFIG_PATH;

        $this->meta = $this->arrayManager->merge(
            $openAmountPath,
            $this->meta,
            [
                'dataType' => 'number',
                'formElement' => Checkbox::NAME,
                'componentType' => Field::NAME,
                'prefer' => 'toggle',
                'valueMap' => [
                    'false' => '0',
                    'true' => '1'
                ],
                'templates' => [
                    'checkbox' => 'ui/form/components/single/switcher',
                ],
                'default' => '0'
            ]
        );

        $amountMinPath = $this->arrayManager->findPath(static::FIELD_OPEN_AMOUNT_MIN, $this->meta, null, 'children');
        $amountMaxPath = $this->arrayManager->findPath(static::FIELD_OPEN_AMOUNT_MAX, $this->meta, null, 'children');

        $this->meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                static::CONTAINER_PREFIX . static::FIELD_OPEN_AMOUNT_MIN,
                $this->meta,
                null,
                'children'
            ),
            $this->meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'component' => 'Magento_Ui/js/form/components/group',
                            'label' => false,
                        ],
                    ],
                ],
            ]
        );

        $this->meta = $this->arrayManager->merge(
            $amountMinPath . static::META_CONFIG_PATH,
            $this->meta,
            [
                'addbefore' => $this->getCurrencySymbol(),
                'validation' => [
                    'validate-number' => true
                ],
                'imports' => [
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::FIELD_ALLOW_OPEN_AMOUNT . ':checked',
                    '__disableTmpl' => ['disabled' => false],
                ],
                'sortOrder' => 10,
                'additionalClasses' => 'admin__field-small'
            ]
        );
        $this->meta = $this->arrayManager->merge(
            $amountMaxPath . static::META_CONFIG_PATH,
            $this->meta,
            [
                'addbefore' => $this->getCurrencySymbol(),
                'validation' => [
                    'validate-number' => true,
                    'greater-than-equals-to' => sprintf('[name*=\'%s\']', static::FIELD_OPEN_AMOUNT_MIN),
                ],
                'imports' => [
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::FIELD_ALLOW_OPEN_AMOUNT . ':checked',
                    '__disableTmpl' => ['disabled' => false],
                ],
                'label' => __('To'),
                'sortOrder' => 20,
                'additionalClasses' => 'admin__field-small admin__field-group-show-label'
            ]
        );
        $this->meta = $this->arrayManager->set(
            $this->arrayManager->slicePath($amountMinPath, 0, -2) . '/children/' . static::FIELD_OPEN_AMOUNT_MAX,
            $this->meta,
            $this->arrayManager->get($amountMaxPath, $this->meta)
        );
        $this->meta = $this->arrayManager->remove($this->arrayManager->slicePath($amountMaxPath, 0, -2), $this->meta);

        return $this;
    }

    /**
     * Customize weight field to depend on card type value
     *
     * @return $this
     */
    protected function customizeWeightField()
    {
        $elementPath = $this->arrayManager->findPath(
            ProductAttributeInterface::CODE_WEIGHT,
            $this->meta,
            null,
            'children'
        );
        $groupCode = $this->arrayManager->slicePath($elementPath, 0, 1);

        $this->meta = $this->arrayManager->merge(
            $elementPath . static::META_CONFIG_PATH,
            $this->meta,
            [
                'component' => 'Magento_GiftCard/component/weight-input',
                'imports' => [
                    'isVirtual' => 'product_form.product_form.' . $groupCode . '.' . static::CONTAINER_PREFIX
                        . static::FIELD_GIFTCARD_TYPE . '.' . static::FIELD_GIFTCARD_TYPE . ':value'
                ]
            ]
        );

        return $this;
    }

    /**
     * Remove "Product Has Weight" field
     *
     * @return $this
     */
    protected function removeHasWeightField()
    {
        $this->meta = $this->arrayManager->remove(
            $this->arrayManager->findPath('product_has_weight', $this->meta, null, 'children'),
            $this->meta
        );

        return $this;
    }

    /**
     * Create "Gift Card Information" panel
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createGiftCardPanel()
    {
        $generalPanelName = $this->getGeneralPanelName($this->meta);

        $this->meta = $this->arrayManager->set(
            static::GROUP_GIFTCARD,
            $this->meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Gift Card Information'),
                            'componentType' => $this->arrayManager->get(
                                $generalPanelName . static::META_CONFIG_PATH . '/componentType',
                                $this->meta
                            ),
                            'dataScope' => $this->arrayManager->get(
                                $generalPanelName . static::META_CONFIG_PATH . '/dataScope',
                                $this->meta
                            ),
                            'sortOrder' => $this->arrayManager->get(
                                $generalPanelName . static::META_CONFIG_PATH . '/sortOrder',
                                $this->meta
                            ) + 1,
                            'collapsible' => true,
                        ],
                    ],
                ],
                'children' => [
                    static::CONTAINER_PREFIX . static::FIELD_IS_REDEEMABLE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => '',
                                    'breakLine' => false,
                                    'formElement' => Container::NAME,
                                    'componentType' => Container::NAME,
                                    'component' => 'Magento_Ui/js/form/components/group',
                                    'sortOrder' => 10,
                                ],
                            ],
                        ],
                        'children' => [
                            static::FIELD_IS_REDEEMABLE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'additionalClasses' => 'admin__field-x-small',
                                            'label' => __('Treat Balance as Store Credit'),
                                            'scopeLabel' => __('[GLOBAL]'),
                                            'prefer' => 'toggle',
                                            'dataScope' => static::FIELD_IS_REDEEMABLE,
                                            'dataType' => 'number',
                                            'formElement' => Checkbox::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 10,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ],
                                            'imports' => [
                                                'disabled' => '${$.parentName}.' . static::FIELD_CONFIG_PREFIX
                                                    . static::FIELD_IS_REDEEMABLE . ':checked',
                                                '__disableTmpl' => ['disabled' => false],
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                            static::FIELD_CONFIG_PREFIX . static::FIELD_IS_REDEEMABLE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'description' => __('Use Config Settings'),
                                            'dataScope' => static::FIELD_CONFIG_PREFIX . static::FIELD_IS_REDEEMABLE,
                                            'dataType' => 'number',
                                            'formElement' => Checkbox::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 20,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ],
                    static::CONTAINER_PREFIX . static::FIELD_LIFETIME => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'breakLine' => false,
                                    'dataScope' => '',
                                    'formElement' => Container::NAME,
                                    'componentType' => Container::NAME,
                                    'component' => 'Magento_Ui/js/form/components/group',
                                    'sortOrder' => 20,
                                ],
                            ],
                        ],
                        'children' => [
                            static::FIELD_LIFETIME => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'additionalClasses' => 'admin__field-small',
                                            'label' => __('Lifetime (days)'),
                                            'scopeLabel' => __('[GLOBAL]'),
                                            'dataScope' => static::FIELD_LIFETIME,
                                            'dataType' => 'int',
                                            'formElement' => Input::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 10,
                                            'validation' => [
                                                'validate-number' => true
                                            ],
                                            'imports' => [
                                                'disabled' => '${$.parentName}.' . static::FIELD_CONFIG_PREFIX
                                                    . static::FIELD_LIFETIME . ':checked',
                                                '__disableTmpl' => ['disabled' => false],
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                            static::FIELD_CONFIG_PREFIX . static::FIELD_LIFETIME => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'description' => __('Use Config Settings'),
                                            'dataScope' => static::FIELD_CONFIG_PREFIX . static::FIELD_LIFETIME,
                                            'dataType' => 'number',
                                            'formElement' => Checkbox::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 20,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ],
                    static::CONTAINER_PREFIX . static::FIELD_ALLOW_MESSAGE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => '',
                                    'breakLine' => false,
                                    'formElement' => Container::NAME,
                                    'componentType' => Container::NAME,
                                    'component' => 'Magento_Ui/js/form/components/group',
                                    'sortOrder' => 30,
                                ],
                            ],
                        ],
                        'children' => [
                            static::FIELD_ALLOW_MESSAGE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'additionalClasses' => 'admin__field-x-small',
                                            'label' => __('Allow Message'),
                                            'scopeLabel' => __('[STORE VIEW]'),
                                            'prefer' => 'toggle',
                                            'dataScope' => static::FIELD_ALLOW_MESSAGE,
                                            'dataType' => 'number',
                                            'formElement' => Select::NAME,
                                            'componentType' => Checkbox::NAME,
                                            'sortOrder' => 10,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ],
                                            'imports' => [
                                                'disabled' => '${$.parentName}.' . static::FIELD_CONFIG_PREFIX
                                                    . static::FIELD_ALLOW_MESSAGE . ':checked',
                                                '__disableTmpl' => ['disabled' => false],
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                            static::FIELD_CONFIG_PREFIX . static::FIELD_ALLOW_MESSAGE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'description' => __('Use Config Settings'),
                                            'dataScope' => static::FIELD_CONFIG_PREFIX . static::FIELD_ALLOW_MESSAGE,
                                            'dataType' => 'number',
                                            'formElement' => Checkbox::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 20,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ],
                    static::CONTAINER_PREFIX . static::FIELD_EMAIL_TEMPLATE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => '',
                                    'breakLine' => false,
                                    'formElement' => Container::NAME,
                                    'componentType' => Container::NAME,
                                    'component' => 'Magento_Ui/js/form/components/group',
                                    'sortOrder' => 40,
                                ],
                            ],
                        ],
                        'children' => [
                            static::FIELD_EMAIL_TEMPLATE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'additionalClasses' => 'admin__field-default',
                                            'label' => __('Email Template'),
                                            'scopeLabel' => __('[STORE VIEW]'),
                                            'dataScope' => static::FIELD_EMAIL_TEMPLATE,
                                            'dataType' => 'select',
                                            'formElement' => Select::NAME,
                                            'componentType' => Field::NAME,
                                            'options' => $this->getEmailTemplates(),
                                            'sortOrder' => 10,
                                            'imports' => [
                                                'disabled' => '${$.parentName}.' . static::FIELD_CONFIG_PREFIX
                                                    . static::FIELD_EMAIL_TEMPLATE . ':checked',
                                                '__disableTmpl' => ['disabled' => false],
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                            static::FIELD_CONFIG_PREFIX . static::FIELD_EMAIL_TEMPLATE => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'description' => __('Use Config Settings'),
                                            'dataScope' => static::FIELD_CONFIG_PREFIX . static::FIELD_EMAIL_TEMPLATE,
                                            'dataType' => 'number',
                                            'formElement' => Checkbox::NAME,
                                            'componentType' => Field::NAME,
                                            'sortOrder' => 20,
                                            'valueMap' => [
                                                'false' => '0',
                                                'true' => '1'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        );

        return $this;
    }

    /**
     * Gift "Gift Card Information" panel fields
     *
     * @return array
     */
    protected function getGiftCardPanelFields()
    {
        return [
            static::FIELD_IS_REDEEMABLE,
            static::FIELD_LIFETIME,
            static::FIELD_ALLOW_MESSAGE,
            static::FIELD_EMAIL_TEMPLATE
        ];
    }

    /**
     * Format amounts to have only two decimals after delimiter
     *
     * @param array $amounts
     * @return array
     */
    protected function formatAmounts(array $amounts)
    {
        foreach ($amounts as $index => $amount) {
            $amounts[$index]['value'] = $this->formatPrice($amount['value']);
        }

        return $amounts;
    }

    /**
     * Get "Gift Card Information" panel data
     *
     * @return array
     */
    protected function getGiftCardPanelData()
    {
        if (!$this->giftCardPanelData) {
            $product = $this->locator->getProduct();
            $id = $product->getId();

            foreach ($this->getGiftCardPanelFields() as $field) {
                $configField = static::FIELD_CONFIG_PREFIX . $field;
                $useConfig = $id ? $product->getData($configField) : '1';

                $this->giftCardPanelData[$field] = $useConfig
                    ? $this->scopeConfig->getValue(GiftCardModel::XML_PATH . $field, ScopeInterface::SCOPE_STORE)
                    : $product->getData($field);
                $this->giftCardPanelData[$configField] = $useConfig;
            }
        }

        return $this->giftCardPanelData;
    }

    /**
     * Retrieve Websites options
     *
     * @param bool $globalScope
     * @return array
     */
    protected function getWebsites($globalScope = false)
    {
        $websites = [
            [
                'value' => '0',
                'label' => __('All Websites [%1]', $this->directoryHelper->getBaseCurrencyCode())
            ]
        ];

        if (!$this->storeManager->hasSingleStore() && !$globalScope) {
            $storeId = $this->locator->getProduct()->getStoreId();

            if ($storeId) {
                $website = $this->storeManager->getStore($storeId)->getWebsite();
                $websites[] = [
                    'value' => $website->getId(),
                    'label' => __(
                        '%1 [%2]',
                        $website->getName(),
                        $website->getConfig(Currency::XML_PATH_CURRENCY_BASE)
                    )
                ];
            } else {
                foreach ($this->storeManager->getWebsites() as $website) {
                    if (!in_array($website->getId(), $this->locator->getProduct()->getWebsiteIds())) {
                        continue;
                    }
                    $websites[] = [
                        'value' => $website->getId(),
                        'label' => __(
                            '%1 [%2]',
                            $website->getName(),
                            $website->getConfig(Currency::XML_PATH_CURRENCY_BASE)
                        )
                    ];
                }
            }
        }

        return $websites;
    }

    /**
     * Retrieve email templates options
     *
     * @return array
     */
    protected function getEmailTemplates()
    {
        /** @var \Magento\Config\Model\Config\Source\Email\Template $template */
        $template = $this->emailTemplateFactory->create();
        $template->setPath(GiftCardModel::XML_PATH_EMAIL_TEMPLATE);

        return $template->toOptionArray();
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    protected function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * Format price according to the current store currency
     *
     * @param mixed $value
     * @return mixed|string
     * @throws \Zend_Currency_Exception
     */
    protected function formatPrice($value)
    {
        $store = $this->locator->getStore();
        if ($store->getId() !== null) {
            $currency = $this->currency->getCurrency($store->getBaseCurrencyCode());
            $value = $currency->toCurrency($value, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        } else {
            $value = parent::formatPrice($value);
        }

        return $value;
    }
}
