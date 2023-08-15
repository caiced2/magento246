<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ResourceConnections\App;

use Magento\Framework\Config\ConfigOptionsListConstants;

class DeploymentConfig extends \Magento\Framework\App\DeploymentConfig
{
    /** Slave section */
    public const SLAVE_CONNECTION = 'slave_connection';

    /** Synchronous replication */
    private const SYNCHRONOUS_REPLICATION = 'synchronous_replication';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var array|null Array for slave config.
     */
    private $slaveConfig = [];

    /**
     * Constructor
     *
     * Data can be optionally injected in the constructor. This object's public interface is intentionally immutable
     *
     * @param \Magento\Framework\App\DeploymentConfig\Reader $reader
     * @param \Magento\Framework\App\Request\Http $requestHttp
     * @param array $overrideData
     */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig\Reader $reader,
        \Magento\Framework\App\Request\Http $requestHttp,
        $overrideData = []
    ) {
        $this->request = $requestHttp;
        parent::__construct($reader, $overrideData);
    }

    /**
     * Gets data from flattened data
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return array|null
     */
    public function get($key = null, $defaultValue = null)
    {
        $this->slaveConfig = parent::get(
            str_replace('connection', self::SLAVE_CONNECTION, $key),
            $defaultValue
        );

        if ($this->request->isSafeMethod() || ($this->slaveConfig[self::SYNCHRONOUS_REPLICATION] ?? false)) {
            $rule = '/^' . preg_quote(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS, '/') . '/';
            if (preg_match($rule, $key)) {
                $config = parent::get($key, $defaultValue);
                $config['slave'] = $this->slaveConfig;
                return $config;
            }
        }
        return parent::get($key, $defaultValue);
    }
}
