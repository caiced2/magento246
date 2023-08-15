<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Adobe console workspace data
 *
 * @api
 * @since 1.1.0
 */
class Workspace
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
    private string $title;

    /**
     * @var string
     */
    private string $actionUrl;

    /**
     * @var string
     */
    private string $appUrl;

    /**
     * @var WorkspaceDetails
     */
    private WorkspaceDetails $details;

    /**
     * Get workspace ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set workspace ID
     *
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get workspace name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set workspace name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get workspace title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set workspace title
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get workspace's action url
     *
     * @return string
     */
    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    /**
     * Set workspace's action url
     *
     * @param string $actionUrl
     */
    public function setActionUrl(string $actionUrl): void
    {
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get workspace's app url
     *
     * @return string
     */
    public function getAppUrl(): string
    {
        return $this->appUrl;
    }

    /**
     * Set workspace's app url
     *
     * @param string $appUrl
     */
    public function setAppUrl(string $appUrl): void
    {
        $this->appUrl = $appUrl;
    }

    /**
     * Get workspace details object
     *
     * @return WorkspaceDetails
     */
    public function getDetails(): WorkspaceDetails
    {
        return $this->details;
    }

    /**
     * Set workspace details object
     *
     * @param WorkspaceDetails $details
     */
    public function setDetails(WorkspaceDetails $details): void
    {
        $this->details = $details;
    }
}
