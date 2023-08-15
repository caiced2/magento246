<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group;

/**
 * Abstract report
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractSection
{
    const NOT_AVAILABLE_DATA = 'n/a';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, array $data = [])
    {
        $this->logger = $logger;
        $this->data = $data;
    }

    /**
     * Execute report generation
     *
     * @return array
     */
    abstract public function generate();

    /**
     * Retrieve value from array by key
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getByKey($data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
