<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Address\File;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\Session;
use Magento\CustomerCustomAttributes\Model\Customer\Address\Attribute\File\Download\Validator;
use Magento\CustomerCustomAttributes\Model\Customer\FileDownload;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;

/**
 * Class Viewfile serves to show file by file name provided in request parameters.
 */
class Viewfile implements ActionInterface, HttpGetActionInterface
{
    /**
     * @var DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Mime
     */
    private $mime;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Validator
     */
    private $downloadValidator;

    /**
     * @var FileDownload
     */
    private $fileDownload;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param FileFactory $fileFactory
     * @param DecoderInterface $urlDecoder
     * @param Mime $mime
     * @param Session $session
     * @param Validator $downloadValidator
     * @param FileDownload $fileDownload
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        FileFactory $fileFactory,
        DecoderInterface $urlDecoder,
        Mime $mime,
        Session $session,
        Validator $downloadValidator,
        FileDownload $fileDownload,
        RequestInterface $request,
        ResultFactory $resultFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->urlDecoder  = $urlDecoder;
        $this->mime = $mime;
        $this->session = $session;
        $this->downloadValidator = $downloadValidator;
        $this->fileDownload = $fileDownload;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Customer view file action
     *
     * @return ResultInterface|ResponseInterface|void
     */
    public function execute()
    {
        $customAttributes = [];
        if ($this->session->isLoggedIn()) {
            $addresses = $this->session->getCustomerData()->getAddresses();
            $customAttributes = [];
            foreach ($addresses as $address) {
                foreach ($address->getCustomAttributes() as $key => $value) {
                    $customAttributes[$key] = $value;
                }
            }
        }

        list($file, $plain) = $this->getFileParams();
        if ($this->downloadValidator->canDownloadFile($file, $customAttributes)) {
            list($fileName, $path) = $this->fileDownload->getFilePath($file);

            $pathInfo = $this->fileDownload->getPathInfo($path);

            if ($plain) {
                return $this->generateImageResult($path);
            } else {
                $name = $pathInfo['basename'];
                return $this->fileFactory->create(
                    $name,
                    ['type' => 'filename', 'value' => $fileName],
                    DirectoryList::MEDIA
                );
            }
        }
    }

    /**
     * Get parameters from request.
     *
     * @return array
     * @throws NotFoundException
     */
    private function getFileParams(): array
    {
        $file = null;
        $plain = false;
        if ($this->request->getParam('file')) {
            // download file
            $file = $this->urlDecoder->decode(
                $this->request->getParam('file')
            );
        } elseif ($this->request->getParam('image')) {
            // show plain image
            $file = $this->urlDecoder->decode(
                $this->request->getParam('image')
            );
            $plain = true;
        } else {
            throw new NotFoundException(__('Page not found.'));
        }

        return [$file, $plain];
    }

    /**
     * Generates raw response of image
     *
     * @param string $path
     * @return Raw
     */
    public function generateImageResult(string $path): Raw
    {
        $directory = $this->fileDownload->getDirectory();
        $contentType = $this->mime->getMimeType($path);
        $stat = $directory->stat($path);
        $contentLength = $stat['size'];
        $contentModify = $stat['mtime'];

        /** @var Raw $resultRaw */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultRaw->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', $contentLength)
            ->setHeader('Last-Modified', date('r', $contentModify));
        $resultRaw->setContents($directory->readFile($path));

        return $resultRaw;
    }
}
