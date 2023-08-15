<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Class PhpInfo
 */
class PhpInfo
{
    /**
     * @var array
     */
    protected $phpInfo;

    /**
     * @var bool
     */
    protected $disablePhpInfo = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Convert phpinfo() HTML output into array and output it in serialized format
     *
     * @link http://www.php.net/manual/en/function.phpinfo.php#106862
     *
     * @return array
     */
    public function getCollectPhpInfo()
    {
        try {
            if (empty($this->phpInfo) && !$this->disablePhpInfo) {
                //@codingStandardsIgnoreStart
                ob_start();
                phpinfo(INFO_ALL);
                $this->phpInfo = [];
                $infoLines = explode("\n", strip_tags(ob_get_clean(), '<tr><td><h2>'));
                //@codingStandardsIgnoreEnd
                $category = 'General';
                foreach ($infoLines as $line) {
                    if (preg_match('~<h2>(.*)</h2>~', $line, $title)) {
                        $category = $title[1];
                    }

                    if (preg_match('~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~', $line, $value)) {
                        $this->phpInfo[$category][trim($value[1])] = trim($value[2]);
                    } elseif (preg_match(
                        '~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~',
                        $line,
                        $value
                    )) {
                        $this->phpInfo[$category][trim($value[1])] = ['local' => $value[2], 'master' => $value[3]];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->disablePhpInfo = true;
            $this->phpInfo = [];
            $this->logger->error($e);
        }

        return $this->phpInfo;
    }

    /**
     * Get all PHP configuration options
     *
     * @param string $extension
     * @param bool $details
     * @return array|null
     */
    public function iniGetAll($extension = null, $details = true)
    {
        try {
            $initInfo = ini_get_all($extension, $details);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $initInfo = null;
        }

        return $initInfo;
    }

    /**
     * Get version of specified PHP module
     *
     * @param string $module
     * @return string|null
     */
    public function getModuleVersion($module = null)
    {
        try {
            $version = phpversion($module);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $version = null;
        }

        return $version;
    }

    /**
     * Get array of loaded PHP extensions
     *
     * @param bool $zendExtensions
     * @return array|null
     */
    public function getLoadedExtensions($zendExtensions = false)
    {
        try {
            $loadedExtensions = get_loaded_extensions($zendExtensions);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $loadedExtensions = null;
        }

        return $loadedExtensions;
    }
}
