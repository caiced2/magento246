<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Helper;

use Magento\Directory\Model\Country;
use Magento\Directory\Model\Region;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url\EncoderInterface;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Config;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Country|MockObject
     */
    protected $countryMock;

    /**
     * @var Region|MockObject
     */
    protected $regionMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var EncoderInterface|MockObject
     */
    protected $urlEncoderMock;

    /**
     * @var Data
     */
    protected $model;

    protected function setUp(): void
    {
        $this->countryMock = $this->createMock(Country::class);
        $this->regionMock = $this->getMockBuilder(Region::class)
            ->addMethods(['getCode'])
            ->onlyMethods(['load', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        $this->storeManagerMock = $arguments['storeManager'];
        /** @var Context $context */
        $context = $arguments['context'];
        $this->urlEncoderMock = $context->getUrlEncoder();
        $this->scopeConfigMock = $context->getScopeConfig();
        $countryFactoryMock = $arguments['countryFactory'];
        $countryFactoryMock->expects($this->any())->method('create')->willReturn($this->countryMock);
        $regionFactoryMock = $arguments['regionFactory'];
        $regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->model = $objectManagerHelper->getObject($className, $arguments);
    }

    /**
     * @dataProvider getReturnAddressDataProvider
     */
    public function testGetReturnAddressData($useStoreAddress, $scopeConfigData, $mockConfig, $expectedResult)
    {
        $this->scopeConfigMock->expects(
            $this->atLeastOnce()
        )->method(
            'isSetFlag'
        )->with(
            Rma::XML_PATH_USE_STORE_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $mockConfig['store_id']
        )->willReturn(
            $useStoreAddress
        );

        $this->scopeConfigMock->expects(
            $this->atLeastOnce()
        )->method(
            'getValue'
        )->willReturnMap(
            $scopeConfigData
        );

        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())
            ->method('getName')
            ->willReturn($mockConfig['country_name']);

        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionMock->expects($this->any())
            ->method('getCode')
            ->willReturn($mockConfig['region_id']);
        $this->regionMock->expects($this->any())
            ->method('getName')
            ->willReturn($mockConfig['region_name']);

        $this->assertEquals($this->model->getReturnAddressData($mockConfig['store_id']), $expectedResult);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getReturnAddressDataProvider()
    {
        return [
            [
                true,
                [
                    [
                        Shipment::XML_PATH_STORE_CITY,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        Shipment::XML_PATH_STORE_COUNTRY_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'AF'
                    ],
                    [
                        Shipment::XML_PATH_STORE_ZIP,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        Shipment::XML_PATH_STORE_REGION_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        Shipment::XML_PATH_STORE_ADDRESS2,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        Shipment::XML_PATH_STORE_ADDRESS1,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        Config::XML_PATH_EMAIL_COPY_TO,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => 'AF',
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => 'Afghanistan',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ],
            ],
            [
                false,
                [
                    [
                        Shipping::XML_PATH_CITY,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        Shipping::XML_PATH_COUNTRY_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'AF'
                    ],
                    [
                        Shipping::XML_PATH_ZIP,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        Shipping::XML_PATH_REGION_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        Shipping::XML_PATH_ADDRESS2,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        Shipping::XML_PATH_ADDRESS1,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        Config::XML_PATH_EMAIL_COPY_TO,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ],
                    [
                        Shipping::XML_PATH_CONTACT_NAME,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Hafizullah Amin'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => 'AF',
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => 'Afghanistan',
                    'firstname' => 'Hafizullah Amin',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ]
            ],
            [
                true,
                [
                    [
                        Shipment::XML_PATH_STORE_CITY,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul',
                    ],
                    [
                        Shipment::XML_PATH_STORE_COUNTRY_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        null
                    ],
                    [
                        Shipment::XML_PATH_STORE_ZIP,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        '912232'
                    ],
                    [
                        Shipment::XML_PATH_STORE_REGION_ID,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Kabul'
                    ],
                    [
                        Shipment::XML_PATH_STORE_ADDRESS2,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 2'
                    ],
                    [
                        Shipment::XML_PATH_STORE_ADDRESS1,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'Test Street 1'
                    ],
                    [
                        Config::XML_PATH_EMAIL_COPY_TO,
                        ScopeInterface::SCOPE_STORE,
                        1,
                        'forshipping@example.com'
                    ]
                ],
                [
                    'store_id' => 1,
                    'country_name' => 'Afghanistan',
                    'region_name' => 'Kabul',
                    'region_id' => 'Kabul'
                ],
                [
                    'city' => 'Kabul',
                    'countryId' => null,
                    'postcode' => '912232',
                    'region_id' => 'Kabul',
                    'street2' => 'Test Street 2',
                    'street1' => 'Test Street 1',
                    'email' => 'forshipping@example.com',
                    'country' => '',
                    'region' => 'Kabul',
                    'company' => null,
                    'telephone' => null
                ]
            ]
        ];
    }

    /**
     * @dataProvider trackProvider
     *
     * @param string $className
     * @param string $key
     * @param string $method
     */
    public function testGetTrackingPopupUrlBySalesModel($className, $key, $method)
    {
        $hash = 'hash';
        $params = [
            '_direct' => 'rma/tracking/popup',
            '_query' => ['hash' => $hash]
        ];
        $url = 'url';

        $trackMock = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(array_intersect(['getProtectCode', 'getId', 'getEntityId'], get_class_methods($className)))
            ->addMethods(array_diff(['getStoreId', 'getProtectCode'], get_class_methods($className)))
            ->getMock();

        $methodResult = 'method result';
        $protectCode = 'protect code';

        $trackMock->expects($this->once())
            ->method($method)
            ->willReturn($methodResult);
        $trackMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectCode);

        $this->urlEncoderMock->expects($this->once())
            ->method('encode')
            ->with("{$key}:{$methodResult}:{$protectCode}")
            ->willReturn($hash);

        $storeModelMock = $this->createMock(Store::class);
        $storeModelMock->expects($this->once())
            ->method('getUrl')
            ->with('', $params)
            ->willReturn($url);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeModelMock);

        $this->assertEquals($url, $this->model->getTrackingPopupUrlBySalesModel($trackMock));
    }

    public function trackProvider()
    {
        return [
            [Rma::class, 'rma_id', 'getId'],
            [Shipping::class, 'track_id', 'getEntityId']
        ];
    }
}
