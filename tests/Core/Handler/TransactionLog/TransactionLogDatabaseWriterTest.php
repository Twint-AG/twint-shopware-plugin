<?php

declare(strict_types=1);

namespace Twint\Tests\Core\Handler\TransactionLog;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Twint\Core\DataAbstractionLayer\Entity\TransactionLog\TwintTransactionLogEntity;
use Twint\Core\Handler\TransactionLog\TransactionLogDatabaseWriter;
use Twint\Sdk\Exception\ApiFailure;
use Twint\Sdk\InvocationRecorder\Value\Invocation;

class TransactionLogDatabaseWriterTest extends TestCase
{
    private EntityRepository $repositoryMock;
    private LoggerInterface $loggerMock;
    private TransactionLogDatabaseWriter $writer;
    private array $transactionLogItem;
    private $invocationMock;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->writer = new TransactionLogDatabaseWriter($this->repositoryMock, $this->loggerMock);
        $this->transactionLogItem = [
            'orderId' => 'order123',
            'orderVersionId' => 'orderVersion123',
            'paymentStateId' => 'payment456',
            'orderStateId' => 'state789',
            'transactionId' => 'trans123',
            'apiMethod' => 'monitorOrder',
            'soapAction' => [],
            'request' => '[]',
            'response' => '[]',
            'soapRequest' => [],
            'soapResponse' => [],
            'exception' => ' '
        ];
        $this->invocationMock = new class(){
            public function arguments(){
                return [];
            }
            public function exception(){
                return null;
            }
            public function methodName(){
                return 'monitorOrder';
            }
            public function returnValue(){
                return [];
            }
            public function messages(){
                return [];
            }
        };
    }

    public function testWrite(): void
    {
        $this->repositoryMock->expects($this->once())
            ->method('create')
            ->with([
                $this->transactionLogItem,
            ], Context::createDefaultContext());

        $this->writer->write(
            $this->transactionLogItem['orderId'],
            $this->transactionLogItem['orderVersionId'],
            $this->transactionLogItem['paymentStateId'],
            $this->transactionLogItem['orderStateId'],
            $this->transactionLogItem['transactionId'],
            $this->transactionLogItem['apiMethod'],
            $this->transactionLogItem['soapAction'],
            $this->transactionLogItem['request'],
            $this->transactionLogItem['response'],
            $this->transactionLogItem['soapRequest'],
            $this->transactionLogItem['soapResponse'],
            $this->transactionLogItem['exception']);
        $this->expectOutputString('');
    }

    public function testWriteObjectLog(): void
    {
        $invocations[] = $this->invocationMock;
        $this->repositoryMock->expects($this->once())
            ->method('create')
            ->with([
                $this->transactionLogItem,
            ], Context::createDefaultContext());

        $this->writer->writeObjectLog($this->transactionLogItem['orderId'], $this->transactionLogItem['orderVersionId'], $this->transactionLogItem['paymentStateId'], $this->transactionLogItem['orderStateId'], $this->transactionLogItem['transactionId'], $invocations);
        $this->expectOutputString('');
    }

    public function testWriteReserveOrderLog(): void
    {
        $invocations[] = $this->invocationMock;
        $this->repositoryMock->expects($this->once())
            ->method('create')
            ->with([
                $this->transactionLogItem,
            ], Context::createDefaultContext());
        $this->writer->writeReserveOrderLog($this->transactionLogItem['orderId'], $this->transactionLogItem['orderVersionId'], $this->transactionLogItem['paymentStateId'], $this->transactionLogItem['orderStateId'], $this->transactionLogItem['transactionId'], $invocations);

        $this->expectOutputString('');
    }

    public function testCheckDuplicatedTransactionLogInLastMinutes(): void
    {
        $this->repositoryMock->expects($this->once())
            ->method('search')
            ->willReturnCallback(function ($criteria, $context) {
                $this->assertInstanceOf(Criteria::class, $criteria);
                $filters = $criteria->getFilters();
                $this->assertCount(8, $filters);
                return new EntitySearchResult(TwintTransactionLogEntity::class, 1, new EntityCollection([]), null, $criteria, $context);
        });
        $result = $this->writer->checkDuplicatedTransactionLogInLastMinutes($this->transactionLogItem);
        $this->assertFalse($result);
    }
}
