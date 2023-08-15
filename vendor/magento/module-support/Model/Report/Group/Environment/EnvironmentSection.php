<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Environment;

use \Magento\Support\Model\ResourceModel\Report\Environment\OsEnvironment;
use \Magento\Support\Model\ResourceModel\Report\Environment\ApacheEnvironment;
use \Magento\Support\Model\ResourceModel\Report\Environment\MysqlEnvironment;
use \Magento\Support\Model\ResourceModel\Report\Environment\PhpEnvironment;
use \Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo;

/**
 * Environment report
 */
class EnvironmentSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Environment Information';

    /**
     * @var array
     */
    protected $phpInfoCollection;

    /**
     * @var \Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo
     */
    protected $phpInfo;

    /**
     * @var OsEnvironment
     */
    protected $osEnvironment;

    /**
     * @var ApacheEnvironment
     */
    protected $apacheEnvironment;

    /**
     * @var MysqlEnvironment
     */
    protected $mysqlEnvironment;

    /**
     * @var PhpEnvironment
     */
    protected $phpEnvironment;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo $phpInfo
     * @param OsEnvironment $osEnvironment
     * @param ApacheEnvironment $apacheEnvironment
     * @param MysqlEnvironment $mysqlEnvironment
     * @param PhpEnvironment $phpEnvironment
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PhpInfo $phpInfo,
        OsEnvironment $osEnvironment,
        ApacheEnvironment $apacheEnvironment,
        MysqlEnvironment $mysqlEnvironment,
        PhpEnvironment $phpEnvironment,
        array $data = []
    ) {
        parent::__construct($logger, $data);
        $this->phpInfo = $phpInfo;
        $this->osEnvironment = $osEnvironment;
        $this->apacheEnvironment = $apacheEnvironment;
        $this->mysqlEnvironment = $mysqlEnvironment;
        $this->phpEnvironment = $phpEnvironment;
    }

    /**
     * Generate Environment information
     *
     * @return array
     */
    public function generate()
    {
        $data = [];

        $this->phpInfoCollection = $this->phpInfo->getCollectPhpInfo();
        if ($this->phpInfoCollection === []) {
            $this->logger->error('Information about current environment will not be fully collected.');
        }

        $data[OsEnvironment::OS_INFORMATION] = $this->osEnvironment->getOsInformation();

        $data[ApacheEnvironment::APACHE_VERSION] = $this->apacheEnvironment->getVersion();
        $data[ApacheEnvironment::APACHE_DOC_ROOT] = $this->apacheEnvironment->getDocumentRoot();
        $data[ApacheEnvironment::APACHE_SRV_ADDRESS] = $this->apacheEnvironment->getServerAddress();
        $data[ApacheEnvironment::APACHE_REMOTE_ADDRESS] = $this->apacheEnvironment->getRemoteAddress();
        $data[ApacheEnvironment::APACHE_LOADED_MODULES] = $this->apacheEnvironment->getLoadedModules();

        $data[MysqlEnvironment::DB_VERSION] = $this->mysqlEnvironment->getVersion();
        $data[MysqlEnvironment::DB_ENGINES] = $this->mysqlEnvironment->getSupportedEngines();
        $data[MysqlEnvironment::DB_AMOUNT] = $this->mysqlEnvironment->getDbAmount();
        $data[MysqlEnvironment::DB_CONFIGURATION] = $this->mysqlEnvironment->getDbConfiguration();
        $data[MysqlEnvironment::DB_PLUGINS] = $this->mysqlEnvironment->getPlugins();

        $data[PhpEnvironment::PHP_VERSION] = $this->phpEnvironment->getVersion();
        $data[PhpEnvironment::PHP_LOADED_INI] = $this->phpEnvironment->getLoadedConfFile();
        $data[PhpEnvironment::PHP_ADDITIONAL_INI] = $this->phpEnvironment->getAdditionalIniFile();
        $data[PhpEnvironment::PHP_CONFIGURATION] = $this->phpEnvironment->getImportantConfigSettings();
        $data[PhpEnvironment::PHP_LOADED_MODULES] = $this->phpEnvironment->getLoadedModules();

        $result = [];
        foreach ($data as $item) {
            if (!empty($item)) {
                $result[] = $item;
            }
        }
        unset($data);

        return [
            self::REPORT_TITLE => [
                'headers' => ['Parameter', 'Value'],
                'data' => $result,
                'count' => sizeof($result)
            ]
        ];
    }
}
