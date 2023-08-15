<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;

/**
 * Gift Wrapping model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Wrapping extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \Magento\GiftWrapping\Api\Data\WrappingInterface
{
    /**
     * Relative path to folder to store wrapping image to
     */
    const IMAGE_PATH = 'wrapping/';

    /**
     * Relative path to folder to store temporary wrapping image to
     */
    const IMAGE_TMP_PATH = 'tmp/wrapping/';

    /**
     * Permitted extensions for wrapping image
     *
     * @var array
     */
    protected $_imageAllowedExtensions = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * Current store id
     *
     * @var int|null
     */
    protected $_store = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;

    /**
     * @var \Magento\GiftWrapping\Model\Wrapping\Validator
     */
    protected $_validator;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param Wrapping\Validator $validator
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\GiftWrapping\Model\Wrapping\Validator $validator,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_systemStore = $systemStore;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_uploaderFactory = $uploaderFactory;
        $this->_validator = $validator;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftWrapping\Model\ResourceModel\Wrapping::class);
    }

    /**
     * Perform actions before object save.
     *
     * @return void
     */
    public function beforeSave()
    {
        if (!$this->hasData('website_ids') && $this->_storeManager->hasSingleStore()) {
            $this->setData('website_ids', array_keys($this->_systemStore->getWebsiteOptionHash()));
        }
        if ($this->hasTmpImage()) {
            $baseImageName = $this->getTmpImage();
            $sourcePath = self::IMAGE_TMP_PATH . $baseImageName;
            $destPath = self::IMAGE_PATH . $baseImageName;
            if ($this->_mediaDirectory->isFile($sourcePath)) {
                $this->_mediaDirectory->renameFile($sourcePath, $destPath);
                $this->setData('image', $baseImageName);
            }
        }
        parent::beforeSave();
    }

    /**
     * Perform actions after object save.
     *
     * @return void
     */
    public function afterSave()
    {
        $this->_getResource()->saveWrappingStoreData($this);
        $this->_getResource()->saveWrappingWebsiteData($this);
    }

    /**
     * Get wrapping associated website ids
     *
     * @return int[]|null
     */
    public function getWebsiteIds()
    {
        if (!$this->hasData(self::WEBSITE_IDS)) {
            $this->setWebsiteIds($this->_getResource()->getWebsiteIds($this->getId()));
        }
        return $this->getData(self::WEBSITE_IDS);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setWebsiteIds(array $ids = null)
    {
        return $this->setData(self::WEBSITE_IDS, $ids);
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     * @codeCoverageIgnore
     */
    public function setStoreId($storeId = null)
    {
        $this->_store = $this->_storeManager->getStore($storeId);
        return $this;
    }

    /**
     * Retrieve store
     *
     * @return \Magento\Store\Model\Store
     * @codeCoverageIgnore
     */
    public function getStore()
    {
        if ($this->_store === null) {
            $this->setStoreId();
        }

        return $this->_store;
    }

    /**
     * Retrieve store id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * Set wrapping image
     *
     * @param string|null|\Magento\MediaStorage\Model\File\Uploader $value
     * @return $this
     */
    public function setImage($value)
    {
        //in the current version should be used instance of \Magento\MediaStorage\Model\File\Uploader
        if ($value instanceof \Magento\Framework\File\Uploader) {
            $value->save($this->_mediaDirectory->getAbsolutePath(self::IMAGE_PATH));
            $value = $value->getUploadedFileName();
        }
        $this->setData('image', $value);
        return $this;
    }

    /**
     * Attach uploaded image to wrapping
     *
     * @param string $imageFieldName
     * @param bool $isTemporary
     * @return $this
     */
    public function attachUploadedImage($imageFieldName, $isTemporary = false)
    {
        $isUploaded = true;
        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_uploaderFactory->create(['fileId' => $imageFieldName]);
            $uploader->setAllowedExtensions($this->_imageAllowedExtensions);
            $uploader->setAllowRenameFiles(true);
            $uploader->setAllowCreateFolders(true);
            $uploader->setFilesDispersion(false);
        } catch (\Exception $e) {
            $isUploaded = false;
        }
        if ($isUploaded) {
            if ($isTemporary) {
                $this->setTmpImage($uploader);
            } else {
                $this->setImage($uploader);
            }
        }
        return $this;
    }

    /**
     * Set image through file contents and return new file name if succeed
     *
     * @param string $fileName
     * @param string $imageContent
     * @return bool|string
     * @throws InputException
     */
    public function attachBinaryImage($fileName, $imageContent)
    {
        if (empty($fileName)) {
            return false;
        }
        if (empty($imageContent)) {
            throw new InputException(__('The image content must be valid data.'));
        }
        $fileNameExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!in_array($fileNameExtension, $this->_imageAllowedExtensions)) {
            throw new InputException(
                __(
                    'The image extension "%1" not allowed.',
                    [$fileNameExtension]
                )
            );
        }
        if (!preg_match('/^[^\\/?*:";<>()|{}\\\\]+$/', $fileName)) {
            throw new InputException(__('Provided image name contains forbidden characters.'));
        }

        $imageProperties = @getimagesizefromstring($imageContent);
        if (empty($imageProperties)) {
            throw new InputException(__('The image content must be valid data.'));
        }
        $sourceMimeType = $imageProperties['mime'];
        if (strpos($sourceMimeType, 'image/') !== 0) {
            throw new InputException(__('The image MIME type is not valid or not supported.'));
        }

        $filePath = $this->_mediaDirectory->getAbsolutePath(self::IMAGE_PATH . $fileName);
        // avoid file names conflicts
        $newFileName = \Magento\MediaStorage\Model\File\Uploader::getNewFileName($filePath);
        $result = $this->_mediaDirectory->writeFile(self::IMAGE_TMP_PATH . $newFileName, $imageContent);
        if ($result) {
            $this->setTmpImage($fileName);
            return $newFileName;
        }
        return false;
    }

    /**
     * Set temporary wrapping image
     *
     * @param string|null|\Magento\MediaStorage\Model\File\Uploader $value
     * @return $this
     */
    public function setTmpImage($value)
    {
        //in the current version should be used instance of \Magento\MediaStorage\Model\File\Uploader
        if ($value instanceof \Magento\Framework\File\Uploader) {
            // Delete previous temporary image if exists
            $this->unsTmpImage();
            $value->save($this->_mediaDirectory->getAbsolutePath(self::IMAGE_TMP_PATH));
            $value = $value->getUploadedFileName();
        }
        $this->setData('tmp_image', $value);
        // Override gift wrapping image name
        $this->setData('image', $value);
        return $this;
    }

    /**
     * Delete temporary wrapping image
     *
     * @return $this
     */
    public function unsTmpImage()
    {
        if ($this->hasTmpImage()) {
            $tmpImagePath = self::IMAGE_TMP_PATH . $this->getTmpImage();
            if ($this->_mediaDirectory->isExist($tmpImagePath)) {
                $this->_mediaDirectory->delete($tmpImagePath);
            }
            $this->unsetData('tmp_image');
        }
        return $this;
    }

    /**
     * Retrieve wrapping image url
     * Function returns url of a temporary wrapping image if it exists
     *
     * @see \Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Helper\Image::_getUrl()
     *
     * @return string|null
     */
    public function getImageUrl()
    {
        if ($this->getTmpImage()) {
            return $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . self::IMAGE_TMP_PATH . $this->getTmpImage();
        }
        if ($this->getImage()) {
            return $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . self::IMAGE_PATH . $this->getImage();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->_validator;
    }

    //@codeCoverageIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function getWrappingId()
    {
        return $this->getData(self::WRAPPING_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setWrappingId($id)
    {
        return $this->setData(self::WRAPPING_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDesign()
    {
        return $this->getData(self::DESIGN);
    }

    /**
     * {@inheritdoc}
     */
    public function setDesign($design)
    {
        return $this->setData(self::DESIGN, $design);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePrice()
    {
        return $this->getData(self::BASE_PRICE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBasePrice($price)
    {
        return $this->setData(self::BASE_PRICE, $price);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageName()
    {
        return $this->getData(self::IMAGE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setImageName($name)
    {
        return $this->setData(self::IMAGE_NAME, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageBase64Content()
    {
        return $this->getData(self::IMAGE_BASE64_CONTENT);
    }

    /**
     * {@inheritdoc}
     */
    public function setImageBase64Content($content)
    {
        return $this->setData(self::IMAGE_BASE64_CONTENT, $content);
    }

    //@codeCoverageIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function getBaseCurrencyCode()
    {
        if (!$this->getData(self::BASE_CURRENCY_CODE)) {
            $this->setBaseCurrencyCode($this->_storeManager->getStore()->getBaseCurrencyCode());
        }
        return $this->getData(self::BASE_CURRENCY_CODE);
    }

    //@codeCoverageIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function setBaseCurrencyCode($code)
    {
        return $this->setData(self::BASE_CURRENCY_CODE, $code);
    }

    //@codeCoverageIgnoreEnd

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface
     * @codeCoverageIgnore
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface $extensionAttributes
     * @return $this
     * @codeCoverageIgnore
     */
    public function setExtensionAttributes(
        \Magento\GiftWrapping\Api\Data\WrappingExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
