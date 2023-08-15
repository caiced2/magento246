<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\General;

/**
 * Data Count report
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DataCountSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Data Count';

    /**
     * Keys for data count entities
     */
    const KEY_DATA = 'data';
    const KEY_REPORT_TYPE = 'report_type';

    /**
     * Methods that generate data count report for entities
     */
    const DATA_COUNT_GENERATE = 'generateDataCount';
    const DATA_COUNT_CATEGORIES_GENERATE = 'generateCategoriesDataCount';
    const DATA_COUNT_ATTRIBUTES = 'generateAttributesDataCount';
    const DATA_COUNT_CUSTOMER_SEGMENTS = 'generateCustomerSegmentsDataCount';
    const DATA_COUNT_PRODUCTS = 'generateProductsDataCount';
    const DATA_COUNT_PRODUCT_ATTRIBUTES_TABLE_SIZE = 'generateProductsAttributesTableSizeDataCount';

    /**
     * @var int
     */
    protected $counter = 0;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $storeResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $taxResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $customerResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $customerSegmentResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $orderResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $catalogResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $salesRuleResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $targetRuleResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $cmsResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $bannerResource;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $urlRewriteResource;

    /**
     * @var \Magento\Support\Model\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes
     */
    protected $attributes;

    /**
     * @var \Magento\Support\Model\ResourceModel\Report\DataCount\ProductAttributes
     */
    protected $productAttributes;

    /**
     * Entities data for report generation
     *
     * @var array
     */
    protected $entities = [
        [
            self::KEY_DATA => [
                'resource' => 'storeResource',
                'tableName' => 'store',
                'title' => 'Stores'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'taxResource',
                'tableName' => 'tax_calculation_rule',
                'title' => 'Tax Rules'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'customerResource',
                'tableName' => 'customer_entity',
                'title' => 'Customers'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'type' => 'customer',
                'resource' => 'customerResource'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_ATTRIBUTES
        ],
        [
            self::KEY_DATA => [
                'type' => 'customer_address',
                'resource' => 'customerResource'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_ATTRIBUTES
        ],
        [
            self::KEY_DATA => [],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_CUSTOMER_SEGMENTS
        ],
        [
            self::KEY_DATA => [
                'resource' => 'orderResource',
                'tableName' => 'sales_order',
                'title' => 'Sales Orders',
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'catalogResource',
                'tableName' => 'catalog_category_entity',
                'title' => 'Categories',
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_CATEGORIES_GENERATE
        ],
        [
            self::KEY_DATA => [
                'type' => 'category',
                'resource' => 'catalogResource'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_ATTRIBUTES
        ],
        [
            self::KEY_DATA => [],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_PRODUCTS
        ],
        [
            self::KEY_DATA => [
                'type' => 'product',
                'resource' => 'catalogResource'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_ATTRIBUTES
        ],
        [
            self::KEY_DATA => [],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_PRODUCT_ATTRIBUTES_TABLE_SIZE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'salesRuleResource',
                'tableName' => 'salesrule',
                'title' => 'Shopping Cart Price Rules'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'salesRuleResource',
                'tableName' => 'catalogrule',
                'title' => 'Catalog Price Rules'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'targetRuleResource',
                'tableName' => 'magento_targetrule',
                'title' => 'Target Rules'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'cmsResource',
                'tableName' => 'cms_page',
                'title' => 'CMS Pages'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'bannerResource',
                'tableName' => 'magento_banner',
                'title' => 'Dynamic Blocks'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'urlRewriteResource',
                'tableName' => 'url_rewrite',
                'title' => 'URL Rewrites'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'storeResource',
                'tableName' => 'cache',
                'title' => 'Core Cache Records'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ],
        [
            self::KEY_DATA => [
                'resource' => 'storeResource',
                'tableName' => 'cache_tag',
                'title' => 'Core Cache Tags'
            ],
            self::KEY_REPORT_TYPE => self::DATA_COUNT_GENERATE
        ]
    ];

    /**
     * Array with generated entity data and count
     *
     * @var array
     */
    protected $dataCount = [];

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\ResourceModel\Store $storeResource
     * @param \Magento\Tax\Model\ResourceModel\TaxClass $taxResource
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $customerSegmentResource
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $catalogResource
     * @param \Magento\SalesRule\Model\ResourceModel\Rule $salesRuleResource
     * @param \Magento\TargetRule\Model\ResourceModel\Rule $targetRuleResource
     * @param \Magento\Cms\Model\ResourceModel\Page $cmsResource
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResource
     * @param \Magento\UrlRewrite\Model\ResourceModel\UrlRewrite $urlRewriteResource
     * @param \Magento\Support\Model\DataFormatter $dataFormatter
     * @param \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes $attributes
     * @param \Magento\Support\Model\ResourceModel\Report\DataCount\ProductAttributes $productAttributes
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\ResourceModel\Store $storeResource,
        \Magento\Tax\Model\ResourceModel\TaxClass $taxResource,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $customerSegmentResource,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Catalog\Model\ResourceModel\Category $catalogResource,
        \Magento\SalesRule\Model\ResourceModel\Rule $salesRuleResource,
        \Magento\TargetRule\Model\ResourceModel\Rule $targetRuleResource,
        \Magento\Cms\Model\ResourceModel\Page $cmsResource,
        \Magento\Banner\Model\ResourceModel\Banner $bannerResource,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewrite $urlRewriteResource,
        \Magento\Support\Model\DataFormatter $dataFormatter,
        \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes $attributes,
        \Magento\Support\Model\ResourceModel\Report\DataCount\ProductAttributes $productAttributes,
        array $data = []
    ) {
        parent::__construct($logger, $data);
        $this->storeResource           = $storeResource;
        $this->taxResource             = $taxResource;
        $this->customerResource        = $customerResource;
        $this->customerSegmentResource = $customerSegmentResource;
        $this->orderResource           = $orderResource;
        $this->catalogResource         = $catalogResource;
        $this->salesRuleResource       = $salesRuleResource;
        $this->targetRuleResource      = $targetRuleResource;
        $this->cmsResource             = $cmsResource;
        $this->bannerResource          = $bannerResource;
        $this->urlRewriteResource      = $urlRewriteResource;
        $this->dataFormatter           = $dataFormatter;
        $this->attributes              = $attributes;
        $this->productAttributes       = $productAttributes;
    }

    /**
     * Generate data and count information
     *
     * Supported counting for:
     * Stores, Tax Rules, Customers, Customer Attributes, Customer Address Attributes, Customer Segments, Orders,
     * Categories, Category Attributes, Products, Product Attributes, Shopping Cart Price Rules, Catalog Price Rules,
     * Target Rules, CMS Pages, Banners, URL Rewrites, Core Cache records, Core Cache Tag records, Log Visitors,
     * Log Visitors Online, Log URLs, Log Quotes, Log Customers
     *
     * @return array
     */
    public function generate()
    {
        foreach ($this->entities as $entity) {
            try {
                switch ($entity[self::KEY_REPORT_TYPE]) {
                    case self::DATA_COUNT_GENERATE:
                        $data = $entity[self::KEY_DATA];
                        $this->generateDataCount($data['resource'], $data['tableName'], $data['title']);
                        $this->counter++;
                        break;
                    case self::DATA_COUNT_CATEGORIES_GENERATE:
                        $data = $entity[self::KEY_DATA];
                        $this->generateCategoriesDataCount($data['resource'], $data['tableName'], $data['title']);
                        $this->counter++;
                        break;
                    case self::DATA_COUNT_ATTRIBUTES:
                        $data = $entity[self::KEY_DATA];
                        $this->generateAttributesDataCount($data['type'], $data['resource']);
                        $this->counter++;
                        break;
                    case self::DATA_COUNT_CUSTOMER_SEGMENTS:
                        $table = $this->customerSegmentResource->getTable('magento_customersegment_segment');
                        $this->generateCustomerSegmentsDataCount($table);
                        $this->counter++;
                        break;
                    case self::DATA_COUNT_PRODUCTS:
                        $table = $this->catalogResource->getTable('catalog_product_entity');
                        $this->generateProductsDataCount($table);
                        $this->counter++;
                        break;
                    case self::DATA_COUNT_PRODUCT_ATTRIBUTES_TABLE_SIZE:
                        $info = $this->productAttributes->getProductAttributesRowSizeForFlatTable();
                        $this->generateProductsAttributesTableSizeDataCount($info);
                        $this->counter++;
                        break;
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        return [
            self::REPORT_TITLE => [
                'headers' => ['Entity', 'Count', 'Extra'],
                'data' => $this->dataCount,
                'count' => $this->counter
            ]
        ];
    }

    /**
     * Generate Data Count
     *
     * @param string $resource
     * @param string $tableName
     * @param string $title
     * @return void
     */
    protected function generateDataCount($resource, $tableName, $title)
    {
        $info = $this->countTableRows($resource, $tableName);
        $this->dataCount[] = [$title, isset($info[0]['cnt']) ? $info[0]['cnt'] : 0];
    }

    /**
     * Generate Categories Data Count
     *
     * @param string $resource
     * @param string $tableName
     * @param string $title
     * @return void
     */
    protected function generateCategoriesDataCount($resource, $tableName, $title)
    {
        $info = $this->countTableRows($resource, $tableName);
        $this->dataCount[] = [$title, isset($info[0]['cnt']) ? --$info[0]['cnt'] : 0];
    }

    /**
     * Count Table Rows
     *
     * @param string $resource
     * @param string $tableName
     * @return array
     */
    protected function countTableRows($resource, $tableName)
    {
        $table = $this->$resource->getTable($tableName);
        $info = $this->$resource->getConnection()->fetchAll("SELECT COUNT(1) as cnt FROM `{$table}`");
        return $info;
    }

    /**
     * Generate Attribute Data Count
     *
     * @param string $attributesType
     * @param string $resource
     * @return void
     */
    protected function generateAttributesDataCount($attributesType, $resource)
    {
        $info = $this->attributes->getAttributesCount($attributesType, $this->$resource);
        foreach ($info as $infoEntry) {
            $this->dataCount[] = $infoEntry;
        }
    }

    /**
     * Generate Customer Segments Data Count
     *
     * @param string $table
     * @return void
     */
    protected function generateCustomerSegmentsDataCount($table)
    {
        $info = $this->customerSegmentResource->getConnection()->fetchAll("SELECT `is_active` FROM `{$table}`");
        if ($info) {
            $counter = 0;
            foreach ($info as $data) {
                if ($data['is_active']) {
                    $counter++;
                }
            }
            $this->dataCount[] = ['Customer Segments', sizeof($info), 'Active Segments: ' . $counter];
        } else {
            $this->dataCount[] = ['Customer Segments', 0];
        }
    }

    /**
     * Generate Products Data Count
     *
     * @param string $table
     * @return void
     */
    protected function generateProductsDataCount($table)
    {
        $info = $this->catalogResource->getConnection()->fetchAll(
            "SELECT COUNT(1) as cnt, `type_id` FROM `{$table}` GROUP BY `type_id`"
        );
        if ($info) {
            $counter = 0;
            $extra = '';
            foreach ($info as $data) {
                $counter += $data['cnt'];
                $extra .= $data['type_id'] . ': ' . $data['cnt'] . '; ';
            }
            $this->dataCount[] = ['Products', $counter, 'Product Types: ' . $extra];
        } else {
            $this->dataCount[] = ['Products', 0];
        }
    }

    /**
     * Generate Products Attributes Flat Table Row Size Data Count
     *
     * @param bool|int $info
     * @return void
     */
    protected function generateProductsAttributesTableSizeDataCount($info)
    {
        $this->dataCount[] = [
            'Product Attributes Flat Table Row Size',
            $info > 0 ? $this->dataFormatter->formatBytes($info) : 'n/a',
            $info . ' bytes'
        ];
    }
}
