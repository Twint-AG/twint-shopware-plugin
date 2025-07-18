<?php

declare(strict_types=1);

namespace Twint\Reporting\ScheduledTask;

use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twint\Reporting\Service\TransactionReportService;
use function round;
use function sprintf;

/**
 * @internal
 */
#[AsMessageHandler(handles: TurnoverReportingTask::class)]
#[Package('checkout')]
class TurnoverReportingTaskHandler extends ScheduledTaskHandler
{
    private const API_IDENTIFIER = '89b03700-3fdf-4a27-a938-70d52c026da9';

    private Client $client;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly LoggerInterface $logger,
        private readonly TransactionReportService $transactionReportService,
        private readonly string $shopwareVersion,
        private readonly ?string $instanceId,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.shopware.com',
        ]);
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $transactionReportIds = $this->transactionReportService->getPaidTransactionReportIds($context);
        $refundTransactionReportIds = $this->transactionReportService->getRefundTransactionReportIds($context);

        $reports = $this->transactionReportService->getAggregatedPaidTurnover($transactionReportIds);
        $refundReports = $this->transactionReportService->getAggregatedRefundTurnover($refundTransactionReportIds);

        $requests = [];
        $refundRequests = [];
        foreach ($reports as $currency => $turnover) {
            $body = $this->buildRequestBody((float) $turnover, (string) $currency);
            $requests[$currency] = $this->client->postAsync(
                '/shopwarepartners/reports/technology',
                [
                    RequestOptions::JSON => $body,
                ]
            );
        }
        foreach ($refundReports as $currency => $turnover) {
            $body = $this->buildRequestBody((float) $turnover, (string) $currency);
            $refundRequests[$currency] = $this->client->postAsync(
                '/shopwarepartners/reports/technology',
                [
                    RequestOptions::JSON => $body,
                ]
            );
        }

        $rejectedCurrencies = [];
        /** @var array{state: string, reason: ClientException} $response */
        foreach (Utils::settle($refundRequests)->wait() as $currency => $response) {
            if ($response['state'] !== Promise::REJECTED) {
                continue;
            }

            $this->logger->warning(sprintf(
                'Failed to report refund for "%s": %s',
                $currency,
                $response['reason']->getMessage()
            ));

            $rejectedCurrencies[] = $currency;
        }
        $rejectedRefundCurrencies = [];
        /** @var array{state: string, reason: ClientException} $response */
        foreach (Utils::settle($requests)->wait() as $currency => $response) {
            if ($response['state'] !== Promise::REJECTED) {
                continue;
            }

            $this->logger->warning(sprintf(
                'Failed to report turnover for "%s": %s',
                $currency,
                $response['reason']->getMessage()
            ));

            $rejectedRefundCurrencies[] = $currency;
        }
        $this->transactionReportService->deleteReports($transactionReportIds, $rejectedCurrencies);
        $this->transactionReportService->deleteReports($refundTransactionReportIds, $rejectedRefundCurrencies);
    }

    public function buildRequestBody(float $amount, string $currency): array
    {
        return [
            'identifier' => self::API_IDENTIFIER,
            'reportDate' => (new DateTime())->format(DateTimeInterface::ATOM),
            'instanceId' => $this->instanceId,
            'shopwareVersion' => $this->shopwareVersion,
            'reportDataKeys' => [
                'turnover' => round($amount, 2),
            ],
            'currency' => $currency,
        ];
    }
}
