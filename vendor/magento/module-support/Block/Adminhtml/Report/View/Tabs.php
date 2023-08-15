<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\View;

use Magento\Support\Model\Report\Config\Converter as ConfigConverter;

/**
 * Tabs widget
 *
 * @api
 * @since 100.0.2
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Support\Model\Report\Config
     */
    protected $reportConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Support\Model\Report\Config $reportConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Support\Model\Report\Config $reportConfig,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->reportConfig = $reportConfig;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('report_info_tabs');
        $this->setDestElementId('view_form');
        $this->setTitle(__('System Report Information'));
    }

    /**
     * Prepare global layout
     *
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        $report = $this->getReport();
        $reportData = $report->prepareReportData();
        if (!$reportData) {
            $this->_logger->error(
                __('Requested system report has no data to display.')
            );
        }

        $installedReportGroups = $this->reportConfig->getGroups();
        foreach ($installedReportGroups as $groupName => $groupConfig) {
            $needToAddTab = false;
            $gridsData = [];
            foreach ($groupConfig[ConfigConverter::KEY_SECTIONS] as $sectionName) {
                if (!array_key_exists($sectionName, $reportData)) {
                    continue;
                }
                $needToAddTab = true;
                $gridsData[$sectionName] = $reportData[$sectionName];
            }

            if ($needToAddTab) {
                /** @var \Magento\Support\Block\Adminhtml\Report\View\Tab $block */
                $block = $this->getLayout()->createBlock(\Magento\Support\Block\Adminhtml\Report\View\Tab::class);
                $block->setGridsData($gridsData);

                $this->addTab(
                    $groupName,
                    [
                        'label' => $groupConfig['title'],
                        'content' => $block->toHtml(),
                    ]
                );
            }
        }
        return parent::_prepareLayout();
    }

    /**
     * Get system report object
     *
     * @return \Magento\Support\Model\Report
     */
    public function getReport()
    {
        if (!($this->getData('report') instanceof \Magento\Support\Model\Report)) {
            $this->setData('report', $this->coreRegistry->registry('current_report'));
        }
        return $this->getData('report');
    }
}
