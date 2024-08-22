<?php

declare(strict_types=1);

namespace Twint\Command;

use DateTime;
use Doctrine\DBAL\Exception\DriverException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Core\DataAbstractionLayer\Entity\Pairing\PairingEntity;
use Twint\Core\Repository\PairingRepository;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;

class TwintPollCommand extends Command
{
    const COMMAND = 'twint:poll';

    public function __construct(
        private readonly PairingRepository $repository,
        private readonly MonitoringService $monitoringService,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('twint:poll');
        $this->addArgument('pairing-id', InputArgument::REQUIRED, 'ID (primary key) of existing TWINT pairings');
        $this->setDescription('Monitoring Pairing');
    }

    /**
     * @throws DriverException
     * @throws PairingException|Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pairingId = $input->getArgument('pairing-id');
        $pairing = $this->repository->load($pairingId, Context::createCLIContext());

        $count = 0;
        $startedAt = new DateTime();

        while (!$pairing->isFinished()) {
            $this->logger->info("TWINT pairing monitor: $pairingId: {$pairing->getVersion()}");
            $this->repository->updateCheckedAt($pairingId);

            $this->monitoringService->monitorOne($pairing);
            $pairing = $this->repository->load($pairingId, Context::createDefaultContext());

            sleep($this->getInterval($pairing, $startedAt));
            ++$count;
        }

        return 0;
    }

    /**
     * Regular: first 3m every 5s, afterwards 10s
     * Express: first 10m every 2s, afterwards 10s
     */
    private function getInterval(PairingEntity $pairing, DateTime $startedAt): int
    {
        $now = new DateTime();
        $interval = $now->diff($startedAt);
        $seconds = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->d * 86400);

        if ($pairing->getIsExpress()) {
            return $seconds < 10 * 60 ? 2 : 10;
        }

        return $seconds < 5 * 60 ? 2 : 10;
    }
}
