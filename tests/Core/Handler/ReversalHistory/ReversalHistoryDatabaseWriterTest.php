<?php

declare(strict_types=1);

namespace Twint\Tests\Core\Handler\ReversalHistory;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Twint\Core\Handler\ReversalHistory\ReversalHistoryDatabaseWriter;
use Twint\Core\Handler\ReversalHistory\ReversalHistoryWriterInterface;

class ReversalHistoryDatabaseWriterTest extends TestCase
{
    private EntityRepository $repositoryMock;
    private LoggerInterface $loggerMock;
    private ReversalHistoryWriterInterface $writer;
    private array $reversalItem;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->writer = new ReversalHistoryDatabaseWriter($this->repositoryMock, $this->loggerMock);
        $this->reversalItem = [
            'orderId' => 'order123',
            'reversalId' => 'rev456',
            'amount' => 100,
            'currency' => 'CHF',
            'reason' => 'Testing reversal',
        ];
    }

    public function testWrite(): void
    {
        $this->repositoryMock->expects($this->once())
            ->method('create')
            ->with([
                $this->reversalItem,
            ], Context::createDefaultContext());

        $this->writer->write($this->reversalItem['orderId'], $this->reversalItem['reversalId'], $this->reversalItem['amount'], $this->reversalItem['currency'], $this->reversalItem['reason']);
        $this->expectOutputString('');
    }

    public function testWriteExceptionHandling(): void
    {
        $this->repositoryMock->expects($this->once())
            ->method('create')
            ->willThrowException(new Exception('Repository error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Repository error');

        $this->writer->write($this->reversalItem['orderId'], $this->reversalItem['reversalId'], $this->reversalItem['amount'], $this->reversalItem['currency'], $this->reversalItem['reason']);
        $this->expectOutputString('');
    }
}