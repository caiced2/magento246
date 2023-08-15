<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Service\V1;

use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Test RmaAttributesManagement
 */
class RmaAttributesManagementTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/returnsAttributeMetadata';

    private const SERVICE_VERSION = 'V1';

    private const SERVICE_NAME = 'rmaRmaAttributesManagementV1';

    /**
     * Test /V1/returnsAttributeMetadata endpoint
     */
    public function testGetAllAttributesMetadata(): void
    {
        $expected = 'resolution';
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'getAllAttributesMetadata',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo);
        $attributeCodes = array_column($result, 'attribute_code');
        $this->assertContains($expected, $attributeCodes);
    }
}
