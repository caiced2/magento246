<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Class ApacheEnvironment
 */
class ApacheEnvironment extends AbstractEnvironment
{
    /**#@+
     * Labels for report
     */
    const APACHE_VERSION = 'Apache Version';
    const APACHE_DOC_ROOT = 'Document Root';
    const APACHE_SRV_ADDRESS = 'Server Address';
    const APACHE_REMOTE_ADDRESS = 'Remote Address';
    const APACHE_LOADED_MODULES = 'Apache Loaded Modules';
    /**#@-*/

    /**#@+
     * Keys of array phpinfo
     */
    const KEY_APACHE_2_HENDLER = 'apache2handler';
    const KEY_APACHE_VERSION = 'Apache Version';
    const KEY_APACHE_ENVIRONMENT = 'Apache Environment';
    const KEY_APACHE_DOCUMENT_ROOT = 'DOCUMENT_ROOT';
    const KEY_PHP_VARIABLES = 'PHP Variables';
    const KEY_SERVER_DOCUMENT_ROOT = '_SERVER["DOCUMENT_ROOT"]';
    const KEY_APACHE_ADDR = 'SERVER_ADDR';
    const KEY_APACHE_PORT = 'SERVER_PORT';
    const KEY_SERVER_ADDR = '_SERVER["SERVER_ADDR"]';
    const KEY_SERVER_PORT = '_SERVER["SERVER_PORT"]';
    const KEY_APACHE_REMOTE_ADDR = 'REMOTE_ADDR';
    const KEY_APACHE_REMOTE_PORT = 'REMOTE_PORT';
    const KEY_SERVER_REMOTE_ADDR = '_SERVER["REMOTE_ADDR"]';
    const KEY_SERVER_REMOTE_PORT = '_SERVER["REMOTE_PORT"]';
    const KEY_LOADED_MODULES = 'Loaded Modules';
    /**#@-*/

    /**
     * Get version of apache
     *
     * @return array
     */
    public function getVersion()
    {
        $data = [];

        if ($this->checkPhpInfo()
            && isset($this->phpInfoCollection[self::KEY_APACHE_2_HENDLER][self::KEY_APACHE_VERSION])
        ) {
            $data = [
                self::APACHE_VERSION,
                $this->phpInfoCollection[self::KEY_APACHE_2_HENDLER][self::KEY_APACHE_VERSION]
            ];
        }

        return $data;
    }

    /**
     * Get document root path
     *
     * @return array
     */
    public function getDocumentRoot()
    {
        $data = [];

        if ($this->checkPhpInfo()) {
            if (isset($this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_DOCUMENT_ROOT])) {
                $data = [
                    self::APACHE_DOC_ROOT,
                    $this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_DOCUMENT_ROOT]
                ];
            } elseif (isset($this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_DOCUMENT_ROOT])) {
                $data = [
                    self::APACHE_DOC_ROOT,
                    $this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_DOCUMENT_ROOT]
                ];
            }
        }

        return $data;
    }

    /**
     * Get address and port of the server
     *
     * @return array
     */
    public function getServerAddress()
    {
        $data = [];

        if ($this->checkPhpInfo()) {
            if (isset($this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_ADDR])
                && isset($this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_PORT])
            ) {
                $data = [
                    self::APACHE_SRV_ADDRESS,
                    $this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_ADDR] . ':'
                        . $this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_PORT]
                ];
            } elseif (isset($this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_ADDR])
                && isset($this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_PORT])
            ) {
                $data = [
                    self::APACHE_SRV_ADDRESS,
                    $this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_ADDR] . ':'
                        . $this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_PORT]
                ];
            }
        }

        return $data;
    }

    /**
     * Get remote address and port of server
     *
     * @return array
     */
    public function getRemoteAddress()
    {
        $data = [];

        if ($this->checkPhpInfo()) {
            if (isset($this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_REMOTE_ADDR])
                && isset($this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_REMOTE_PORT])
            ) {
                $data = [
                    self::APACHE_REMOTE_ADDRESS,
                    $this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_REMOTE_ADDR] . ':'
                        . $this->phpInfoCollection[self::KEY_APACHE_ENVIRONMENT][self::KEY_APACHE_REMOTE_PORT]
                ];
            } elseif (isset($this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_REMOTE_ADDR])
                && isset($this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_REMOTE_PORT])
            ) {
                $data = [
                    self::APACHE_REMOTE_ADDRESS,
                    $this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_REMOTE_ADDR] . ':'
                        . $this->phpInfoCollection[self::KEY_PHP_VARIABLES][self::KEY_SERVER_REMOTE_PORT]
                ];
            }
        }

        return $data;
    }

    /**
     * Get loaded modules of apache
     *
     * @return array
     */
    public function getLoadedModules()
    {
        $data = [];

        if ($this->checkPhpInfo()
            && isset($this->phpInfoCollection[self::KEY_APACHE_2_HENDLER][self::KEY_LOADED_MODULES])
        ) {
            $modulesInfo = '';
            $modules = explode(' ', $this->phpInfoCollection[self::KEY_APACHE_2_HENDLER][self::KEY_LOADED_MODULES]);
            foreach ($modules as $module) {
                $modulesInfo .= $module . "\n";
            }
            $data = [self::APACHE_LOADED_MODULES, trim($modulesInfo)];
        }

        return $data;
    }
}
