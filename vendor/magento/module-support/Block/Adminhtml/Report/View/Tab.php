<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\View;

/**
 * Tab widget
 */
class Tab extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var \Magento\Support\Model\Report\DataConverter
     */
    protected $dataConverter;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Support\Model\Report\DataConverter $dataConverter
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Support\Model\Report\DataConverter $dataConverter,
        array $data = []
    ) {
        $this->dataConverter = $dataConverter;
        parent::__construct($context, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Magento_Support::report/view/tab.phtml');
    }

    /**
     * Get system report grid blocks
     *
     * @return array
     */
    public function getGrids()
    {
        $grids = [];
        $gridsData = $this->getGridsData();
        if (empty($gridsData) || !is_array($gridsData)) {
            return $grids;
        }
        foreach ($gridsData as $reports) {
            foreach ($reports as $title => $data) {
                $grids[$title] = new \Magento\Framework\DataObject();
                $titleHash = $this->getEncryptor()->getHash($title);
                if (!empty($data['error'])) {
                    $grids[$title]->setDataCount(0);
                    $grids[$title]->setError($data['error']);
                    $grids[$title]->setTitleHash($titleHash);
                    continue;
                }

                /** @var \Magento\Support\Block\Adminhtml\Report\View\Tab\Grid $block */
                $block = $this->getLayout()->createBlock(\Magento\Support\Block\Adminhtml\Report\View\Tab\Grid::class);
                $block->setId('grid_' . $titleHash)
                    ->setGridData($data);
                $grids[$title]->setDataCount($data['count']);
                $grids[$title]->setGridObject($block);
                $grids[$title]->setTitleHash($titleHash);
            }
        }
        return $grids;
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Report');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Get status flag whether this tab can be shown
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Get status flag whether this tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * The getter function to get encryptor for real application code
     *
     * @return \Magento\Framework\Encryption\EncryptorInterface
     *
     * @deprecated 100.1.0
     */
    private function getEncryptor()
    {
        if ($this->encryptor === null) {
            $this->encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
        }

        return $this->encryptor;
    }
}
