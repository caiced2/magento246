<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Block\Adminhtml\Report\Export;

use Magento\Support\Model\Report\Config\Converter as ConfigConverter;

/**
 * Export html
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Html extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Report data limitation
     */
    const MAX_NONE_COLLAPSIBLE_ROW_AMOUNT = 64;

    /**
     * @var \Magento\Support\Model\Report\Config
     */
    protected $reportConfig;

    /**
     * @var \Magento\Support\Model\Report\DataConverter
     */
    protected $dataConverter;

    /**
     * @var \Magento\Support\Model\Report\HtmlGenerator
     */
    protected $htmlGenerator;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * List of reports by groups
     *
     * @var null|array
     */
    protected $reports = null;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Support\Model\Report\Config $reportConfig
     * @param \Magento\Support\Model\Report\DataConverter $dataConverter
     * @param \Magento\Support\Model\Report\HtmlGenerator $htmlGenerator
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Support\Model\Report\Config $reportConfig,
        \Magento\Support\Model\Report\DataConverter $dataConverter,
        \Magento\Support\Model\Report\HtmlGenerator $htmlGenerator,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->reportConfig = $reportConfig;
        $this->dataConverter = $dataConverter;
        $this->htmlGenerator = $htmlGenerator;
        $this->localeResolver = $localeResolver;
        $this->logger = $context->getLogger();
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
        $this->setTemplate('Magento_Support::report/export/html.phtml');
    }

    /**
     * Get data converter
     *
     * @return \Magento\Support\Model\Report\DataConverter
     */
    public function getDataConverter()
    {
        return $this->dataConverter;
    }

    /**
     * Get html generator
     *
     * @return \Magento\Support\Model\Report\HtmlGenerator
     */
    public function getHtmlGenerator()
    {
        return $this->htmlGenerator;
    }

    /**
     * Get system report
     *
     * @return \Magento\Support\Model\Report
     */
    public function getReport()
    {
        return $this->getData('report');
    }

    /**
     * Get prepared report data by groups
     *
     * @return array
     */
    public function getReports()
    {
        $report = $this->getReport();
        if (!$report->getId()) {
            return [];
        }

        if ($this->reports !== null) {
            return $this->reports;
        }

        $reportData = $report->prepareReportData();
        if (!$reportData) {
            $this->logger->error(__('Requested system report has no data to output.'));
        }

        $this->reports = [];
        $installedReportGroups = $this->reportConfig->getGroups();
        foreach ($installedReportGroups as $groupName => $groupConfig) {
            foreach ($groupConfig[ConfigConverter::KEY_SECTIONS] as $sectionName) {
                if (!array_key_exists($sectionName, $reportData)) {
                    continue;
                }

                if (!isset($this->reports[$groupName])) {
                    $this->reports[$groupName] = [
                        'title' => $groupConfig['title'],
                        'reports' => []
                    ];
                }
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $this->reports[$groupName]['reports'] = array_merge(
                    $this->reports[$groupName]['reports'],
                    $reportData[$sectionName]
                );
            }
        }
        return $this->reports;
    }

    /**
     * Get system report title
     *
     * @return string
     */
    public function getReportTitle()
    {
        $report = $this->getReport();
        if (!$report->getId()) {
            return '';
        }
        return $report->getClientHost();
    }

    /**
     * Get system report creation date
     *
     * @return string
     */
    public function getReportCreationDate()
    {
        $report = $this->getReport();
        if (!$report->getId()) {
            return '';
        }
        return $this->_localeDate->formatDateTime(
            new \DateTime($report->getCreatedAt() ?? 'now'),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
    }

    /**
     * Get copyright text
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getCopyrightText()
    {
        $report = $this->getReport();
        if (!$report->getId()) {
            return '';
        }

        $text = __('&copy; Magento Commerce Inc., %1', date('Y'));
        return $text;
    }

    /**
     * Get language code
     *
     * @return string|null
     */
    public function getLang()
    {
        if (!$this->hasData('lang')) {
            $this->setData('lang', substr($this->localeResolver->getLocale(), 0, 2));
        }
        return $this->getData('lang');
    }

    /**
     * Get hash for string
     *
     * @param string $str
     * @return string
     */
    public function getHash($str)
    {
        return $this->getEncryptor()->getHash($str);
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
            // phpcs:ignore Magento2.PHP.AutogeneratedClassNotInConstructor
            $this->encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
        }

        return $this->encryptor;
    }
}
