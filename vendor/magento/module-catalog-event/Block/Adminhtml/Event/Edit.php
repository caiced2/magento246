<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Events edit page
 */
namespace Magento\CatalogEvent\Block\Adminhtml\Event;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\CatalogEvent\Model\Event;
use Magento\Framework\Registry;

/**
 * @api
 * @since 100.0.2
 */
class Edit extends Container
{
    /**
     * @var string
     */
    protected $_objectId = 'id';

    /**
     * @var string
     */
    protected $_blockGroup = 'Magento_CatalogEvent';

    /**
     * @var string
     */
    protected $_controller = 'adminhtml_event';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Prepare catalog event form or category selector
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if (!$this->getEvent()->getId() && !$this->getEvent()->getCategoryId()) {
            $this->buttonList->remove('save');
            $this->buttonList->remove('reset');
        } else {
            $this->buttonList->add(
                'save_and_continue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                1
            );
        }

        if (!$this->getEvent()->getId() && !$this->getEvent()->getCategoryId()) {
            $this->setChild(
                'form',
                $this->getLayout()->createBlock(
                    str_replace(
                        '_',
                        '\\',
                        $this->_blockGroup
                    ) . '\\Block\\' . str_replace(
                        ' ',
                        '\\',
                        ucwords(str_replace('_', ' ', $this->_controller . '_' . $this->_mode))
                    ) . '\Category',
                    $this->getNameInLayout() . 'catalog_event_form'
                )
            );
        }

        if ($this->getRequest()->getParam('category')) {
            $this->buttonList->update('back', 'label', __('Back to Category'));
        }

        if ($this->getEvent()->isReadonly() && $this->getEvent()->getImageReadonly()) {
            $this->buttonList->remove('save');
            $this->buttonList->remove('reset');
            $this->buttonList->remove('save_and_continue');
        }

        if (!$this->getEvent()->isDeleteable()) {
            $this->buttonList->remove('delete');
        }

        parent::_prepareLayout();

        return $this;
    }

    /**
     * Retrieve form back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRequest()->getParam('category')) {
            return $this->getUrl(
                'catalog/category/edit',
                ['clear' => 1, 'id' => $this->getEvent()->getCategoryId()]
            );
        } elseif ($this->getEvent() && !$this->getEvent()->getId() && $this->getEvent()->getCategoryId()) {
            return $this->getUrl('*/*/new', ['_current' => true, 'category_id' => null]);
        }

        return parent::getBackUrl();
    }

    /**
     * Retrieve form container header
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->getEvent()->getId()) {
            return __('Edit Catalog Event');
        } else {
            return __('Add Catalog Event');
        }
    }

    /**
     * Retrieve catalog event model
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->_coreRegistry->registry('magento_catalogevent_event');
    }
}
