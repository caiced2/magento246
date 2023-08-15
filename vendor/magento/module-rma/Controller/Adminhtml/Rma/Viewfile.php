<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryResolver;

/**
 * Controller to view file or image by file/image name provided in request parameters.
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Viewfile extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var DirectoryResolver
     */
    private $directoryResolver;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Shipping\Helper\Carrier $carrierHelper
     * @param \Magento\Rma\Model\Shipping\LabelService $labelService
     * @param \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param DirectoryResolver|null $directoryResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Shipping\Helper\Carrier $carrierHelper,
        \Magento\Rma\Model\Shipping\LabelService $labelService,
        \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper,
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        DirectoryResolver $directoryResolver = null
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $filesystem,
            $carrierHelper,
            $labelService,
            $rmaDataMapper
        );
        $this->resultRawFactory = $resultRawFactory;
        $this->urlDecoder = $urlDecoder;
        $this->directoryResolver = $directoryResolver ?: $this->_objectManager->get(DirectoryResolver::class);
    }

    /**
     * Retrieve image MIME type by its extension
     *
     * @param string $extension
     * @return string
     */
    protected function _getPlainImageMimeType($extension)
    {
        $mimeTypeMap = ['gif' => 'image/gif', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
        $contentType = 'application/octet-stream';
        if (isset($mimeTypeMap[$extension])) {
            $contentType = $mimeTypeMap[$extension];
        }
        return $contentType;
    }

    /**
     * Action for view full sized item attribute image
     *
     * @return void
     * @throws NotFoundException
     */
    public function execute()
    {
        $fileName = null;
        $plain = false;
        if ($this->getRequest()->getParam('file')) {
            // download file
            $fileName = $this->urlDecoder->decode(
                $this->getRequest()->getParam('file')
            );
        } elseif ($this->getRequest()->getParam('image')) {
            // show plain image
            $fileName = $this->urlDecoder->decode(
                $this->getRequest()->getParam('image')
            );
            $plain = true;
        } else {
            throw new NotFoundException(__('Page not found.'));
        }

        $filePath = sprintf('rma_item/%s', $fileName);
        $fileAbsolutePath = $this->readDirectory->getAbsolutePath($filePath);
        if (!$this->readDirectory->isExist($filePath)
            || !$this->directoryResolver->validatePath($fileAbsolutePath, DirectoryList::MEDIA)
        ) {
            throw new NotFoundException(__('Page not found.'));
        }

        if ($plain) {
            /** @var $readFile \Magento\Framework\Filesystem\File\Read */
            $readFile = $this->readDirectory->openFile($filePath);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $contentType = $this->_getPlainImageMimeType(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));
            $fileStat = $this->readDirectory->stat($filePath);

            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultRawFactory->create();
            $resultRaw->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Content-type', $contentType, true)
                ->setHeader('Content-Length', $fileStat['size'])
                ->setHeader('Last-Modified', date('r', $fileStat['mtime']));
            $resultRaw->setContents($readFile->read($fileStat['size']));
            return $resultRaw;
        } else {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $name = pathinfo($fileName, PATHINFO_BASENAME);
            $this->_fileFactory->create(
                $name,
                ['type' => 'filename', 'value' => $fileAbsolutePath],
                DirectoryList::MEDIA
            );
        }
    }
}
