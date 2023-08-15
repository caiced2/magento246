<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model;

/**
 * Report model
 *
 * @api
 * @property \Magento\Support\Model\ResourceModel\Report _resource
 * @method array getReportData()
 * @method array getReportGroups()
 * @method string getClientHost()
 * @method string getCreatedAt()
 * @method string getMagentoVersion()
 * @method bool hasReportData()
 * @method bool hasReportGroups()
 * @method bool hasClientHost()
 * @method bool hasCreatedAt()
 * @method bool hasMagentoVersion()
 * @method $this setReportData(array $value)
 * @method $this setReportGroups(array $value)
 * @method $this setClientHost(string $value)
 * @method $this setCreatedAt(string $value)
 * @method $this setMagentoVersion(string $value)
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Report extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Support\Model\Report\Config
     */
    protected $reportConfig;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Support\Model\Report\DataConverter
     */
    protected $dataConverter;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $dateFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     * @since 100.1.0
     */
    protected $productMetadata;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Support\Model\ResourceModel\Report $resource
     * @param \Magento\Support\Model\ResourceModel\Report\Collection $resourceCollection
     * @param \Magento\Support\Model\Report\Config $reportConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Support\Model\Report\DataConverter $dataConverter
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Support\Model\ResourceModel\Report $resource,
        \Magento\Support\Model\ResourceModel\Report\Collection $resourceCollection,
        \Magento\Support\Model\Report\Config $reportConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Support\Model\Report\DataConverter $dataConverter,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        array $data = []
    ) {
        $this->reportConfig = $reportConfig;
        $this->objectManager = $objectManager;
        $this->dataConverter = $dataConverter;
        $this->dateFactory = $dateFactory;
        $this->timeZone = $timeZone;
        $this->productMetadata = $productMetadata;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Support\Model\ResourceModel\Report::class);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $this->setCreatedAt($this->dateFactory->create()->gmtDate());
        $this->setMagentoVersion($this->productMetadata->getVersion());
        return parent::beforeSave();
    }

    /**
     * Populate report with data
     *
     * @param array|string $groups
     * @return void
     */
    public function generate($groups)
    {
        $sections = $this->reportConfig->getSectionNamesByGroup($groups);

        $reportData = [];
        foreach ($sections as $section) {
            /** @var \Magento\Support\Model\Report\Group\AbstractSection $sectionModel */
            $sectionModel = $this->objectManager->create(
                $section,
                ['data' => $this->reportConfig->getSectionData($section)]
            );
            $reportData[$section] = $sectionModel->generate();
        }

        $this->setReportGroups($groups);
        $this->setReportData($reportData);
    }

    /**
     * Prepare report data for output in HTML format
     *
     * @return bool|array
     */
    public function prepareReportData()
    {
        $reportData = $this->getReportData();
        if (empty($reportData) || !is_array($reportData)) {
            return false;
        }

        $preparedData = [];
        foreach ($reportData as $section => $reports) {
            if (!is_array($reports) || empty($reports)) {
                continue;
            }
            foreach ($reports as $title => $data) {
                try {
                    $preparedData[$section][$title] = $this->dataConverter->prepareData($data);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $preparedData[$section][$title] = ['error' => $e->getMessage()];
                    $this->_logger->critical($e);
                }
            }
        }
        return $preparedData;
    }

    /**
     * Get file name for system report download action
     *
     * @return string
     */
    public function getFileNameForReportDownload()
    {
        if (!$this->getId()) {
            return '';
        }
        $host = $this->getClientHost();
        $host = preg_replace('~[^-_.a-z0-9]+~', '', $host);
        $createdDate = $this->timeZone->formatDateTime(
            new \DateTime($this->getCreatedAt() ?? 'now'),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM,
            null,
            null,
            'Y-MM-dd-HH-mm-ss'
        );

        return 'report-' . $createdDate . ($host ? '_' . $host : '') . '.html';
    }
}
