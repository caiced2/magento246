<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Rma\Status;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Api\Data\CommentInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

/**
 * RMA model
 * @method \Magento\Rma\Model\Rma\Status\History setStoreId(int $storeId)
 * @method \Magento\Rma\Model\Rma\Status\History setEmailSent(bool $value)
 * @method bool getEmailSent()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class History extends \Magento\Sales\Model\AbstractModel implements CommentInterface
{
    /**#@+
     * Data object properties
     */
    const ENTITY_ID = 'entity_id';
    const RMA_ENTITY_ID = 'rma_entity_id';
    const IS_CUSTOMER_NOTIFIED = 'is_customer_notified';
    const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';
    const COMMENT = 'comment';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const IS_ADMIN = 'is_admin';

    /** @deprecated  use IS_ADMIN instead */
    const ADMIN = 'admin';

    /** @deprecated  use IS_CUSTOMER_NOTIFIED instead */
    const CUSTOMER_NOTIFIED = 'customer_notified';

    /** @deprecated use IS_VISIBLE_ON_FRONT instead */
    const VISIBLE_ON_FRONT = 'visible_on_front';

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Rma factory
     *
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $_rmaFactory;

    /**
     * Rma configuration
     *
     * @var \Magento\Rma\Model\Config
     */
    protected $_rmaConfig;

    /**
     * Mail transport builder
     *
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * Core date model 2.0
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTimeDateTime;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * Message manager
     *
     * @var \Magento\Rma\Api\RmaAttributesManagementInterface
     */
    protected $metadataService;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param \Magento\Rma\Model\Config $rmaConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTimeDateTime
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param TimezoneInterface $localeDate
     * @param RmaRepositoryInterface $rmaRepositoryInterface
     * @param RmaAttributesManagementInterface $metadataService
     * @param AddressRenderer $addressRenderer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Magento\Rma\Model\Config $rmaConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTimeDateTime,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Rma\Helper\Data $rmaHelper,
        TimezoneInterface $localeDate,
        RmaRepositoryInterface $rmaRepositoryInterface,
        RmaAttributesManagementInterface $metadataService,
        AddressRenderer $addressRenderer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_rmaFactory = $rmaFactory;
        $this->_rmaConfig = $rmaConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->dateTimeDateTime = $dateTimeDateTime;
        $this->inlineTranslation = $inlineTranslation;
        $this->rmaHelper = $rmaHelper;
        $this->localeDate = $localeDate;
        $this->rmaRepository = $rmaRepositoryInterface;
        $this->metadataService = $metadataService;
        $this->addressRenderer = $addressRenderer;
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
     * @inheritdoc
     */
    protected function getCustomAttributesCodes()
    {
        if ($this->customAttributesCodes === null) {
            $this->customAttributesCodes = $this->getEavAttributesCodes($this->metadataService);
        }
        return $this->customAttributesCodes;
    }

    /**
     * @inheritdoc
     *
     * @return \Magento\Rma\Api\Data\CommentExtensionInterface
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param \Magento\Rma\Api\Data\CommentExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\CommentExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnoreStart
     */
    public function getComment()
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * @inheritdoc
     */
    public function setComment($comment)
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getRmaEntityId()
    {
        return $this->getData(self::RMA_ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRmaEntityId($rmaId)
    {
        return $this->setData(self::RMA_ENTITY_ID, $rmaId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function isCustomerNotified()
    {
        return $this->getData(self::IS_CUSTOMER_NOTIFIED);
    }

    /**
     * @inheritdoc
     */
    public function setIsCustomerNotified($isCustomerNotified)
    {
        return $this->setData(self::IS_CUSTOMER_NOTIFIED, $isCustomerNotified);
    }

    /**
     * @inheritdoc
     */
    public function isVisibleOnFront()
    {
        return $this->getData(self::IS_VISIBLE_ON_FRONT);
    }

    /**
     * @inheritdoc
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        return $this->setData(self::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function isAdmin()
    {
        return $this->getData(self::IS_ADMIN);
    }

    /**
     * @inheritdoc
     */
    public function setIsAdmin($isAdmin)
    {
        return $this->setData(self::IS_ADMIN, $isAdmin);
    }

    //@codeCoverageIgnoreEnd

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Rma\Model\ResourceModel\Rma\Status\History::class);
    }

    /**
     * Get store object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        if ($this->getOrder()) {
            return $this->getOrder()->getStore();
        }
        return $this->_storeManager->getStore();
    }

    /**
     * Get RMA object
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->rmaRepository->get($this->getRmaEntityId());
    }

    /**
     * Sending email with comment data
     *
     * @return $this
     */
    public function sendCommentEmail()
    {
        $order = $this->getRma()->getOrder();
        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }
        $sendTo = [['email' => $order->getCustomerEmail(), 'name' => $customerName]];

        return $this->_sendCommentEmail($this->_rmaConfig->getRootCommentEmail(), $sendTo, true);
    }

    /**
     * Sending email to admin with customer's comment data
     *
     * @return $this
     */
    public function sendCustomerCommentEmail()
    {
        $rmaModel = $this->rmaRepository->get($this->getRmaEntityId());
        $sendTo = [
            [
                'email' => $this->_rmaConfig->getCustomerEmailRecipient($rmaModel->getStoreId()),
                'name' => null,
            ],
        ];
        return $this->_sendCommentEmail(
            $this->_rmaConfig->getRootCustomerCommentEmail(),
            $sendTo,
            false,
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
        );
    }

    /**
     * Sending email to admin with customer's comment data
     *
     * @param string $rootConfig Current config root
     * @param array $sendTo mail recipient array
     * @param bool $isGuestAvailable
     * @param string $templateArea
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _sendCommentEmail(
        $rootConfig,
        $sendTo,
        $isGuestAvailable = true,
        $templateArea = \Magento\Framework\App\Area::AREA_FRONTEND
    ) {
        $rma = $this->getRma();

        $this->_rmaConfig->init($rootConfig, $rma->getStoreId());
        if (!$this->_rmaConfig->isEnabled()) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $copyTo = $this->_rmaConfig->getCopyTo();
        $copyMethod = $this->_rmaConfig->getCopyMethod();

        if ($isGuestAvailable && $rma->getOrder()->getCustomerIsGuest()) {
            $template = $this->_rmaConfig->getGuestTemplate();
        } else {
            $template = $this->_rmaConfig->getTemplate();
        }

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }

        if ($templateArea == \Magento\Framework\App\Area::AREA_FRONTEND) {
            $storeId = $rma->getStoreId();
        } else {
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }
        $store = $this->_storeManager->getStore($storeId);
        $identity = $this->_rmaConfig->getIdentity('', $storeId);

        foreach ($sendTo as $recipient) {
            $transport = $this->_transportBuilder->setTemplateIdentifier($template)
                ->setTemplateOptions(
                    ['area' => $templateArea, 'store' => $storeId]
                )
                ->setTemplateVars(
                    [
                        'rma' => $rma,
                        'rma_data' => [
                            'status_label' => $rma->getStatusLabel(),
                        ],
                        'order' => $rma->getOrder(),
                        'order_data' => [
                            'customer_name' => $rma->getOrder()->getCustomerName(),
                        ],
                        'comment' => $this->getComment(),
                        'supportEmail' => $store->getConfig('trans_email/ident_support/email'),
                    ]
                )
                ->setFromByScope($identity, $storeId)
                ->addTo($recipient['email'], $recipient['name'])
                ->addBcc($bcc)
                ->getTransport();

            $transport->sendMessage();
        }
        $this->setEmailSent(true);

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * Save system comment.
     *
     * @return void
     */
    public function saveSystemComment()
    {
        $status = $this->getRma()->getStatus();
        $comment = self::getSystemCommentByStatus($status);
        $this->saveComment($comment, true, true);
    }

    /**
     * Get system comment by state. Returns null if state is not known.
     *
     * @param string $status
     * @return string|null
     */
    public static function getSystemCommentByStatus($status) // phpcs:ignore Magento2.Functions.StaticFunction
    {
        $comments = [
            Status::STATE_PENDING => __('We placed your Return request.'),
            Status::STATE_AUTHORIZED => __('We authorized your Return request.'),
            Status::STATE_PARTIAL_AUTHORIZED => __('We partially authorized your Return request.'),
            Status::STATE_RECEIVED => __('We received your Return request.'),
            Status::STATE_RECEIVED_ON_ITEM => __('We partially received your Return request.'),
            Status::STATE_APPROVED_ON_ITEM => __('We partially approved your Return request.'),
            Status::STATE_REJECTED_ON_ITEM => __('We partially rejected your Return request.'),
            Status::STATE_CLOSED => __('We closed your Return request.'),
            Status::STATE_PROCESSED_CLOSED => __('We processed and closed your Return request.'),
        ];
        return isset($comments[$status]) ? $comments[$status] : null;
    }

    /**
     * Set comment into RMA.
     *
     * @param string $comment
     * @param bool $visibleOnFrontend
     * @param bool $isAdmin
     * @return void
     */
    public function saveComment($comment, $visibleOnFrontend, $isAdmin = false)
    {
        /** @var \Magento\Rma\Api\Data\RmaInterface $rma */
        $rma = $this->getRma();
        $this->setRmaEntityId($rma->getEntityId());
        $this->setComment($comment);
        $this->setIsVisibleOnFront($visibleOnFrontend);
        $this->setStatus($rma->getStatus());
        $this->setCreatedAt($this->dateTimeDateTime->gmtDate());
        $this->setIsCustomerNotified($this->getEmailSent());
        $this->setIsAdmin($isAdmin);
        $this->save();
    }

    /**
     * Sending email with RMA data
     *
     * @return $this
     */
    public function sendNewRmaEmail()
    {
        return $this->_sendRmaEmailWithItems($this->getRma(), $this->_rmaConfig->getRootRmaEmail());
    }

    /**
     * Sending authorizing email with RMA data
     *
     * @return $this
     */
    public function sendAuthorizeEmail()
    {
        $rma = $this->getRma();
        return $this->_sendRmaEmailWithItems($rma, $this->_rmaConfig->getRootAuthEmail());
    }

    /**
     * Sending authorizing email with RMA data
     *
     * @param Rma $rma
     * @param string $rootConfig
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _sendRmaEmailWithItems(Rma $rma, $rootConfig)
    {
        $storeId = $rma->getStoreId();
        $order = $rma->getOrder();

        $this->_rmaConfig->init($rootConfig, $storeId);
        if (!$this->_rmaConfig->isEnabled()) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $copyTo = $this->_rmaConfig->getCopyTo();
        $copyMethod = $this->_rmaConfig->getCopyMethod();

        if ($order->getCustomerIsGuest()) {
            $template = $this->_rmaConfig->getGuestTemplate();
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $template = $this->_rmaConfig->getTemplate();
            $customerName = $rma->getCustomerName();
        }

        $sendTo = [['email' => $order->getCustomerEmail(), 'name' => $customerName]];
        if ($rma->getCustomerCustomEmail() && $rma->getCustomerCustomEmail() !== $order->getCustomerEmail()) {
            $sendTo[] = ['email' => $rma->getCustomerCustomEmail(), 'name' => $customerName];
        }
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = ['email' => $email, 'name' => null];
            }
        }

        $returnAddress = $this->rmaHelper->getReturnAddress('html', [], $storeId);

        $bcc = [];
        if ($copyTo && $copyMethod == 'bcc') {
            $bcc = $copyTo;
        }
        $store = $this->_storeManager->getStore($storeId);
        $identity = $this->_rmaConfig->getIdentity('', $storeId);

        try {
            foreach ($sendTo as $recipient) {
                $transport = $this->_transportBuilder->setTemplateIdentifier($template)
                    ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                    ->setTemplateVars(
                        [
                            'rma' => $rma,
                            'rma_id' => $rma->getId(),
                            'rma_data' => [
                                'status_label' => is_string($rma->getStatusLabel()) ?
                                    $rma->getStatusLabel() : $rma->getStatusLabel()->render(),
                            ],
                            'order' => $order,
                            'order_data' => [
                                'customer_name' => $order->getCustomerName(),
                            ],
                            'created_at_formatted_1' => $rma->getCreatedAtFormated(1),
                            'store' => $store,
                            'return_address' => $returnAddress,
                            'item_collection' => $rma->getItemsForDisplay(),
                            'formattedShippingAddress' => $this->addressRenderer->format(
                                $order->getShippingAddress(),
                                'html'
                            ),
                            'formattedBillingAddress' => $this->addressRenderer->format(
                                $order->getBillingAddress(),
                                'html'
                            ),
                            'supportEmail' => $store->getConfig('trans_email/ident_support/email'),
                            'storePhone' => $store->getConfig('general/store_information/phone'),
                        ]
                    )
                    ->setFromByScope($identity, $storeId)
                    ->addTo($recipient['email'], $recipient['name'])
                    ->addBcc($bcc)
                    ->getTransport();

                $transport->sendMessage();
            }

            $this->setEmailSent(true);
        } catch (MailException $exception) {
            $this->_logger->critical($exception->getMessage());
        }

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * Get object created at date affected current active store timezone
     *
     * @return \DateTime
     */
    public function getCreatedAtDate()
    {
        return $this->localeDate->date(new \DateTime($this->getCreatedAt()));
    }
}
