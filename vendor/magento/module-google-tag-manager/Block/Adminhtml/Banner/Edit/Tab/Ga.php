<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Framework\App\ObjectManager;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * @api
 * @since 100.0.2
 */
class Ga extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Representation value of enabled banner
     */
    public const STATUS_ENABLED = 1;

    /**
     * Representation value of disabled banner
     */
    public const STATUS_DISABLED  = 0;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param array $data
     * @param TagManagerConfig|null $tagManagerConfig
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\GoogleTagManager\Helper\Data $helper,
        array $data = [],
        TagManagerConfig $tagManagerConfig = null
    ) {
        $this->helper = $helper;
        $this->tagManagerConfig = $tagManagerConfig ?? ObjectManager::getInstance()->get(
            TagManagerConfig::class
        );
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Set form id prefix, add customer segment binding, set values if banner is editing
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        if (!$this->helper->isGoogleAnalyticsAvailable() && !$this->tagManagerConfig->isGoogleAnalyticsAvailable()) {
            return $this;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $htmlIdPrefix = 'banner_googleanalytics_settings_';
        $form->setHtmlIdPrefix($htmlIdPrefix);

        $model = $this->_coreRegistry->registry('current_banner');

        $fieldset = $form->addFieldset(
            'ga_fieldset',
            ['legend' => __('Google Analytics Enhanced Ecommerce Settings')]
        );

        $fieldset->addField('is_ga_enabled', 'select', [
            'label'     => __('Send to Google'),
            'name'      => 'is_ga_enabled',
            'required'  => false,
            'options'   => [
                self::STATUS_ENABLED  => __('Yes'),
                self::STATUS_DISABLED => __('No'),
            ],
        ]);
        if (!$model->getId()) {
            $model->setData('is_ga_enabled', self::STATUS_ENABLED);
        }

        $fieldset->addField('ga_creative', 'text', [
            'label'     => __('Creative'),
            'name'      => 'ga_creative',
            'required'  => false,
        ]);

        $form->setValues($model->getData());
        $this->setForm($form);

        return $this;
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Google Analytics Enhanced Ecommerce Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Returns status flag whether this tab can be shown or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag whether this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }
}
