<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Test\Unit\Adminhtml\System\Config;

use Exception;
use InvalidArgumentException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\QuickCheckout\Model\Adminhtml\System\Config\AccountCredentials;
use Magento\QuickCheckout\Model\Adminhtml\System\Config\CredentialsValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @see CredentialsValidator
 */
class CredentialsValidatorTest extends TestCase
{
    private const API_KEY = 'drcgkr9pkdlj2epbhddu9veuzjemqbvfthxl7wzkcufuskvhtpfae86mknv3k2ck';
    private const SIGNING_SECRET = 'jkshgjkdhfgjkafdgkjasgkhjalsghakjfg4h4j3knhksfsadfdfsdfsd';
    private const PUB_KEY = 'aaaa.division_1.bbbb';
    private const ALT_PUB_KEY = 'bbbb.division_1.aaaa';
    private const INVALID_API_KEY = 'api_key';
    private const INVALID_PUB_KEY = 'pub_key';

    /**
     * @var CredentialsValidator
     */
    private $credentialsValidator;

    /**
     * @var ClientInterface|MockObject
     */
    private $serviceClient;

    /**
     * @var TransferFactoryInterface|MockObject
     */
    private $transferFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->serviceClient = $this->createMock(ClientInterface::class);
        $this->transferFactory = $this->createMock(TransferFactoryInterface::class);

        $this->credentialsValidator = new CredentialsValidator($this->transferFactory, $this->serviceClient);
    }

    public function testShouldFailForInvalidKeys(): void
    {
        $creds = new AccountCredentials(self::INVALID_API_KEY, self::SIGNING_SECRET, self::INVALID_PUB_KEY);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid publishable key');
        $this->whenTheCredentialsAreTested($creds);
    }

    public function testShouldFailIfThereAreNoMerchantDivisions(): void
    {
        $creds = new AccountCredentials(self::API_KEY, self::SIGNING_SECRET, self::PUB_KEY);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find any merchant division');
        $this->givenThereAreNoMerchantDivisions();
        $this->whenTheCredentialsAreTested($creds);
    }

    public function testShouldFailIfThereIsNoMatchingMerchantDivision(): void
    {
        $creds = new AccountCredentials(self::API_KEY, self::SIGNING_SECRET, self::PUB_KEY);

        $merchantDivisions = [
            ['division_id' => 'division_2', 'publishable_key' => 'some_key',]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find a matching merchant division');
        $this->givenThereAreSomeMerchantDivisions($merchantDivisions);
        $this->whenTheCredentialsAreTested($creds);
    }

    public function testShouldFailIfThePubKeyOfTheMerchantDivisionDoesNotMatch(): void
    {
        $creds = new AccountCredentials(self::API_KEY, self::SIGNING_SECRET, self::PUB_KEY);

        $merchantDivisions = [
            ['division_id' => 'division_1', 'publishable_key' => self::ALT_PUB_KEY,]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Publishable key mismatch');
        $this->givenThereAreSomeMerchantDivisions($merchantDivisions);
        $this->whenTheCredentialsAreTested($creds);
    }

    public function givenThereAreNoMerchantDivisions()
    {
        $this->prepareTransferRequest();
        $this->serviceClient->method('placeRequest')->willReturn([]);
    }

    public function givenThereAreSomeMerchantDivisions($merchantDivisions)
    {
        $this->prepareTransferRequest();
        $this->serviceClient->method('placeRequest')->willReturn([
            'merchant_divisions' => $merchantDivisions,
        ]);
    }

    public function prepareTransferRequest()
    {
        $request = $this->createMock(TransferInterface::class);
        $this->transferFactory->method('create')->willReturn($request);
    }

    private function whenTheCredentialsAreTested(AccountCredentials $command)
    {
        $this->credentialsValidator->validate($command);
    }
}
