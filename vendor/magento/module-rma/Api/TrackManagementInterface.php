<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api;

/**
 * Interface TrackManagementInterface
 * @api
 * @since 100.0.2
 */
interface TrackManagementInterface
{
    /**
     * Get shipping label int the PDF format
     *
     * @param int $id
     * @return string
     */
    public function getShippingLabelPdf($id);

    /**
     * Get track list
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\TrackSearchResultInterface
     */
    public function getTracks($id);

    /**
     * Add track
     *
     * @param int $id
     * @param \Magento\Rma\Api\Data\TrackInterface $track
     * @return bool
     */
    public function addTrack($id, \Magento\Rma\Api\Data\TrackInterface $track);

    /**
     * Remove track by id
     *
     * @param int $id
     * @param int $trackId
     * @return bool
     */
    public function removeTrackById($id, $trackId);
}
