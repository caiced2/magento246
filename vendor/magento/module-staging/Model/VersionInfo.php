<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

class VersionInfo
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $rowId;

    /**
     * @var string
     */
    private $createdIn;

    /**
     * @var string
     */
    private $updatedIn;

    /**
     * VersionInfo constructor.
     *
     * @param string $rowId
     * @param string $identifier
     * @param string $createdIn
     * @param string $updatedIn
     */
    public function __construct($rowId, $identifier, $createdIn, $updatedIn)
    {
        $this->rowId = $rowId;
        $this->identifier = $identifier;
        $this->createdIn = $createdIn;
        $this->updatedIn = $updatedIn;
    }

    /**
     * @return string
     */
    public function getRowId()
    {
        return $this->rowId;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getCreatedIn()
    {
        return $this->createdIn;
    }

    /**
     * @return string
     */
    public function getUpdatedIn()
    {
        return $this->updatedIn;
    }
}
