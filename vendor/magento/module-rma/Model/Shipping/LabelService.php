<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Shipping;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LabelService
{
    /**
     * @var \Magento\Rma\Helper\Data
     */
    private $rmaHelper;

    /**
     * @var \Magento\Rma\Model\ShippingFactory
     */
    private $shippingFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\ShippingFactory
     */
    private $shippingResourceFactory;

    /**
     * Application filesystem
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Json instance
     *
     * @var Json
     */
    private $json;

    /**
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param \Magento\Rma\Model\ShippingFactory $shippingFactory
     * @param \Magento\Rma\Model\ResourceModel\ShippingFactory $shippingResourceFactory
     * @param Filesystem $filesystem
     * @param Json $json
     */
    public function __construct(
        \Magento\Rma\Helper\Data $rmaHelper,
        \Magento\Rma\Model\ShippingFactory $shippingFactory,
        \Magento\Rma\Model\ResourceModel\ShippingFactory $shippingResourceFactory,
        Filesystem $filesystem,
        Json $json = null
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->shippingFactory = $shippingFactory;
        $this->shippingResourceFactory = $shippingResourceFactory;
        $this->filesystem = $filesystem;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Create shipping label for specific shipment with validation
     *
     * @param \Magento\Rma\Model\Rma $rmaModel
     * @param array $data
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createShippingLabel(\Magento\Rma\Model\Rma $rmaModel, $data = [])
    {
        if (empty($data['packages'])) {
            return false;
        }
        $carrier = $this->rmaHelper->getCarrier($data['code'], $rmaModel->getStoreId());
        if (!$carrier->isShippingLabelsAvailable()) {
            return false;
        }

        /** @var $shippingModel \Magento\Rma\Model\Shipping */
        $shippingModel = $this->shippingFactory->create();
        /** @var $shipment \Magento\Rma\Model\Shipping */
        $shipment = $shippingModel->getShippingLabelByRma($rmaModel);

        $shipment->setPackages($data['packages']);
        $shipment->setCode($data['code']);

        list($carrierCode, $methodCode) = explode('_', $data['code'], 2);
        $shipment->setCarrierCode($carrierCode);
        $shipment->setMethodCode($methodCode);

        $shipment->setCarrierTitle($data['carrier_title']);
        $shipment->setMethodTitle($data['method_title']);
        $shipment->setPrice($data['price']);
        $shipment->setRma($rmaModel);
        $shipment->setIncrementId($rmaModel->getIncrementId());
        $weight = 0;
        foreach ($data['packages'] as $package) {
            $weight += $package['params']['weight'];
        }
        $shipment->setWeight($weight);

        $response = $shipment->requestToShipment();

        if ($response->hasErrors() || !$response->hasInfo()) {
            throw new \Magento\Framework\Exception\LocalizedException(__($response->getErrors()));
        }

        $labelsContent = [];
        $trackingNumbers = [];
        $info = $response->getInfo();

        foreach ($info as $inf) {
            if (!empty($inf['tracking_number']) && !empty($inf['label_content'])) {
                $labelsContent[] = $inf['label_content'];
                if (is_array($inf['tracking_number'])) {
                    $trackingNumbers = array_merge($trackingNumbers, array_values($inf['tracking_number']));
                } else {
                    $trackingNumbers[] = $inf['tracking_number'];
                }
            }
        }
        $outputPdf = $this->combineLabelsPdf($labelsContent);
        $shipment->setPackages($this->json->serialize($data['packages']));
        $shipment->setShippingLabel($outputPdf->render());
        $shipment->setIsAdmin(\Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_LABEL);
        $shipment->setRmaEntityId($rmaModel->getId());
        $shipment->save();

        if ($trackingNumbers) {
            /** @var $shippingResource \Magento\Rma\Model\ResourceModel\Shipping */
            $shippingResource = $this->shippingResourceFactory->create();
            $shippingResource->deleteTrackingNumbers($rmaModel);
            foreach ($trackingNumbers as $trackingNumber) {
                $this->addTrack(
                    $rmaModel->getId(),
                    $trackingNumber,
                    $carrier->getCarrierCode(),
                    $carrier->getConfigData('title'),
                    \Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_LABEL_TRACKING_NUMBER
                );
            }
        }
        return true;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rmaModel
     *
     * @throws \Exception
     * @return string
     */
    public function getShippingLabelByRmaPdf(\Magento\Rma\Model\Rma $rmaModel)
    {
        /** @var $shippingModel \Magento\Rma\Model\Shipping */
        $shippingModel = $this->shippingFactory->create();
        /** @var $shipment \Magento\Rma\Model\Shipping */
        $labelContent = $shippingModel->getShippingLabelByRma($rmaModel)->getShippingLabel();
        if ($labelContent) {
            $pdfContent = null;
            if (stripos($labelContent, '%PDF-') !== false) {
                $pdfContent = $labelContent;
            } else {
                $pdf = new \Zend_Pdf();
                $page = $this->createPdfPageFromImageString($labelContent);
                $incrementId = $rmaModel->getIncrementId();
                if (!$page) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("We don't recognize or support the file extension in shipment %1.", $incrementId)
                    );
                }
                $pdf->pages[] = $page;
                $pdfContent = $pdf->render();
            }

            return $pdfContent;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Shipment does not exists.'));
        }
    }

    /**
     * Combine Labels Pdf
     *
     * @param string[] $labelsContent
     * @return \Zend_Pdf
     */
    public function combineLabelsPdf(array $labelsContent)
    {
        $outputPdf = new \Zend_Pdf();
        foreach ($labelsContent as $content) {
            if (stripos($content, '%PDF-') !== false) {
                $pdfLabel = \Zend_Pdf::parse($content);
                foreach ($pdfLabel->pages as $page) {
                    $outputPdf->pages[] = clone $page;
                }
            } else {
                $page = $this->createPdfPageFromImageString($content);
                if ($page) {
                    $outputPdf->pages[] = $page;
                }
            }
        }
        return $outputPdf;
    }

    /**
     * Create \Zend_Pdf_Page instance with image from $imageString. Supports JPEG, PNG, GIF, WBMP, and GD2 formats.
     *
     * @param string $imageString
     * @return \Zend_Pdf_Page|bool
     */
    public function createPdfPageFromImageString($imageString)
    {
        $image = imagecreatefromstring($imageString);
        if (!$image) {
            return false;
        }

        $xSize = imagesx($image);
        $ySize = imagesy($image);
        $page = new \Zend_Pdf_Page($xSize, $ySize);

        imageinterlace($image, 0);
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $tmpFileName = 'shipping_labels_' . uniqid(\Magento\Framework\Math\Random::getRandomNumber()) . time() . '.png';
        $tmpFilePath = $dir->getAbsolutePath($tmpFileName);
        imagepng($image, $tmpFilePath);
        $pdfImage = \Zend_Pdf_Image::imageWithPath($tmpFilePath);
        $page->drawImage($pdfImage, 0, 0, $xSize, $ySize);
        $dir->delete($tmpFileName);
        return $page;
    }

    /**
     * @param int $trackId
     * @param int $rmaId
     * @return bool
     * @throws \Exception
     */
    public function removeTrack($trackId, $rmaId)
    {
        /** @var $shippingModel \Magento\Rma\Model\Shipping */
        $shippingModel = $this->shippingFactory->create();
        $shippingModel->load($trackId);
        if ($shippingModel->getId() && $shippingModel->getRmaEntityId() == $rmaId) {
            $shippingModel->delete();
        } else {
            throw new \Exception(
                __('We can\'t load track with retrieving identifier right now.')
            );
        }

        return true;
    }

    /**
     * @param int $id
     * @param string $number
     * @param string $carrier
     * @param string $title
     * @param int|null $isAdmin
     *
     * @return bool
     * @throws \Exception
     */
    public function addTrack($id, $number, $carrier = '', $title = '', $isAdmin = null)
    {
        try {
            if ($isAdmin === null) {
                $isAdmin = \Magento\Rma\Model\Shipping::IS_ADMIN_STATUS_ADMIN_TRACKING_NUMBER;
            }
            /** @var $shippingModel \Magento\Rma\Model\Shipping */
            $shippingModel = $this->shippingFactory->create();
            $shippingModel->setTrackNumber($number)
                ->setCarrierCode(
                    ($carrier) ?: 'custom'
                )->setCarrierTitle($title)
                ->setRmaEntityId($id)
                ->setIsAdmin($isAdmin)
                ->save();
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }
        return true;
    }
}
