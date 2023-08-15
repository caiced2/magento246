<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report;

/**
 * Form widget for viewing report
 *
 * @api
 * @since 100.0.2
 */
class View extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'Magento_Support';
        $this->_controller = 'adminhtml_report';
        $this->_mode = 'view';

        $removalConfirmation = 'deleteConfirm(\''. __('Are you sure you want to delete the system report?')
            . '\', \'' . $this->getDeleteUrl() . '\')';

        $this->buttonList->remove('reset');
        $this->buttonList->update('save', 'label', __('Download'));
        $this->buttonList->update('save', 'onclick', 'setLocation(\'' . $this->getDownloadUrl() . '\')');
        $this->buttonList->update('delete', 'onclick', $removalConfirmation);

        $this->buttonList->add(
            'go_to_top',
            [
                'label' => __('Go to Top'),
                'onclick' => 'setLocation(\'#top\')',
                'class' => 'go'
            ],
            0,
            1
        );
    }

    /**
     * Get current report model
     *
     * @return \Magento\Support\Model\Report
     */
    public function getReport()
    {
        return $this->coreRegistry->registry('current_report');
    }

    /**
     * Get download URL
     *
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->getUrl(
            '*/*/download',
            [$this->_objectId => $this->getRequest()->getParam($this->_objectId)]
        );
    }
}
