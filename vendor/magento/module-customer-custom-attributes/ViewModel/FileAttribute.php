<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\ViewModel;

use Magento\Customer\Model\FileProcessorFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;
use Magento\Framework\File\Mime;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ObjectManager;

/**
 * View model for custom attributes form block
 */
class FileAttribute implements ArgumentInterface
{
    /**
     * @var Url
     */
    private $url;

    /**
     * Filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Media Directory object (writable).
     *
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var FileProcessorFactory
     */
    private $fileProcessorFactory;

    /**
     * @var Mime
     */
    private $mime;

    /**
     * @var string
     */
    private $uploadUrl;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var File
     */
    private $ioFile;

    /**
     * @var string
     */
    private $downloadUrl;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;


    /**
     * @param Url $url
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param FileProcessorFactory $fileProcessorFactory
     * @param Mime $mime
     * @param string $uploadUrl
     * @param string $entityType
     * @param File $ioFile
     */
    public function __construct(
        Url $url,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        FileProcessorFactory $fileProcessorFactory,
        Mime $mime,
        string $uploadUrl,
        string $entityType,
        File $ioFile,
        string $downloadUrl = '',
        UrlInterface $urlBuilder = null,
        EncoderInterface $urlEncoder = null
    ) {
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileProcessorFactory = $fileProcessorFactory;
        $this->mime = $mime;
        $this->uploadUrl = $uploadUrl;
        $this->entityType = $entityType;
        $this->ioFile = $ioFile;
        $this->downloadUrl = $downloadUrl;
        $this->urlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlInterface::class);
        $this->urlEncoder = $urlEncoder ?? ObjectManager::getInstance()->get(EncoderInterface::class);
    }

    /**
     * Get json definition for js Ui component fields
     *
     * @param array $userAttributes
     * @param AbstractModel $entity
     * @return string
     */
    public function getJsComponentsDefinitions(
        array $userAttributes,
        AbstractModel $entity
    ): string {
        $result = [];
        foreach ($userAttributes as $attribute) {
            $config = [];
            $frontendInput = $attribute->getFrontendInput();

            if (in_array($frontendInput, ['file', 'image'])) {
                $config['component'] = 'Magento_CustomerCustomAttributes/js/component/file-uploader';
                $config['template'] = 'Magento_CustomerCustomAttributes/form/element/uploader/uploader';
                $config['label'] = $attribute->getDefaultFrontendLabel();
                $config['formElement'] = 'fileUploader';
                $config['componentType'] = 'fileUploader';
                $config['uploaderConfig'] = [
                    'url' => $this->url->getUrl(
                        $this->uploadUrl
                    )
                ];

                $config['dataScope'] = $attribute->getAttributeCode();

                $filename = $entity->getData($attribute->getAttributeCode());

                if ($filename) {
                    $filePath = $this->entityType . $filename;
                    $fileInfo = $this->mediaDirectory->stat($filePath);

                    $fileAbsolutePath = $this->mediaDirectory->getAbsolutePath() . $filePath;
                    $config['value'] = [
                        [
                            'file' => $filename,
                            'name' => $this->ioFile->getPathInfo($filename)['basename'],
                            'size' => $fileInfo['size'],
                            'url' => $this->urlBuilder->getUrl(
                                $this->downloadUrl,
                                ['file' => $this->urlEncoder->encode(ltrim($filename, '/'))]
                            ),
                            'type' => $this->mime->getMimeType($fileAbsolutePath),
                        ]
                    ];
                }

                if ($attribute->getIsRequired()) {
                    $config['validation'] = [
                        'required' => true,
                    ];
                    $config['required'] = '1';
                }
            }

            $result[$attribute->getAttributeCode()] = $config;
        }

        return json_encode($result);
    }
}
