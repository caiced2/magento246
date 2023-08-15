<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * IMS Org data
 *
 * @api
 * @since 1.1.0
 */
class Organization
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $imsOrgId;

    /**
     * Return ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Return Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return IMS Org ID
     *
     * @return string
     */
    public function getImsOrgId(): string
    {
        return $this->imsOrgId;
    }

    /**
     * Set IMS Org ID
     *
     * @param string $imsOrgId
     */
    public function setImsOrgId(string $imsOrgId): void
    {
        $this->imsOrgId = $imsOrgId;
    }
}
