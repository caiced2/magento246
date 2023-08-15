<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Backup;

/**
 * Render Log information
 *
 * @api
 * @since 100.0.2
 */
class Log extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var \Magento\Support\Model\Backup;
     */
    protected $backup;

    /**
     * @var \Magento\Support\Model\BackupFactory;
     */
    protected $backupFactory;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Support\Model\BackupFactory $backupFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Support\Model\BackupFactory $backupFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->backupFactory = $backupFactory;

        $this->addButton('back', [
            'label'   => __('Back'),
            'onclick' => "setLocation('" . $this->getUrl('*/*/'). "')",
            'class'   => 'back'
        ]);
    }

    /**
     * Header text getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Backup Log Details');
    }

    /**
     * Get Backup
     *
     * @return \Magento\Support\Model\Backup
     */
    public function getBackup()
    {
        if (!$this->backup) {
            $this->backup = $this->backupFactory->create()->load($this->getRequest()->getParam('id', 0));
        }

        return $this->backup;
    }

    /**
     * Set Backup
     *
     * @param \Magento\Support\Model\Backup $backup
     * @return void
     */
    public function setBackup(\Magento\Support\Model\Backup $backup)
    {
        $this->backup = $backup;
    }
}
