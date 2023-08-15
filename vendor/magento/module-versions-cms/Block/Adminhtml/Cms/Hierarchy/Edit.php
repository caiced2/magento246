<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy;

/**
 * Cms Page Tree Edit Form Container Block
 *
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Cms data
     *
     * @var \Magento\VersionsCms\Helper\Data
     */
    protected $_cmsData;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\VersionsCms\Helper\Data $cmsData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\VersionsCms\Helper\Data $cmsData,
        array $data = []
    ) {
        $this->_cmsData = $cmsData;
        parent::__construct($context, $data);
    }

    /**
     * Initialize Form Container
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'node_id';
        $this->_blockGroup = 'Magento_VersionsCms';
        $this->_controller = 'adminhtml_cms_hierarchy';

        parent::_construct();

        $this->buttonList->update('save', 'onclick', 'hierarchyNodes.save()');
        $this->buttonList->remove('back');
        $this->buttonList->add(
            'delete',
            [
                'label' => __('Delete Current Hierarchy'),
                'class' => 'delete',
                'onclick' => 'deleteCurrentHierarchy()'
            ],
            -1,
            1
        );

        if (!$this->_storeManager->hasSingleStore()) {
            $this->buttonList->add(
                'delete_multiple',
                [
                    'label' => $this->_cmsData->getDeleteMultipleHierarchiesText(),
                    'class' => 'delete',
                    'onclick' => "openHierarchyDialog('delete')"
                ],
                -1,
                7
            );
            $this->buttonList->add(
                'copy',
                ['label' => __('Copy'), 'class' => 'add', 'onclick' => "openHierarchyDialog('copy')"],
                -1,
                14
            );
        }
    }

    /**
     * Retrieve text for header element
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Pages Hierarchy');
    }
}
