<?php

declare(strict_types=1);

namespace Twint\Command;

use DateTime;
use Doctrine\DBAL\Exception\DriverException;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Twint\Core\Repository\PairingRepository;
use Twint\ExpressCheckout\Exception\PairingException;
use Twint\ExpressCheckout\Service\Monitoring\MonitoringService;

class TwintPollCommand extends Command
{
    const COMMAND = 'twint:poll';
    const LIMIT_POLLING = 180; // 30 mins

    public function __construct(
        private readonly PairingRepository $repository,
        private readonly MonitoringService $monitoringService
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
        $io = new SymfonyStyle($input, $output);

        $pairingId = $input->getArgument('pairing-id');

        $io->info('Starting to load entity');
        $pairing = $this->repository->load($pairingId, Context::createCLIContext());
        $io->info('Entity loaded');

        $count = 0;
        while (!$pairing->isFinished() && $count < self::LIMIT_POLLING) {
            $this->log($pairingId, $pairing->getVersion(), "Polling TWINT");

            $this->monitoringService->monitorOne($pairing);
            $pairing = $this->repository->load($pairingId, Context::createCLIContext());

            sleep(1); // Make this more intelligent
            ++$count;
        }

        return 0;
    }

    private function log(...$args): void
    {
        $message = array_pop($args);

        file_put_contents(
            '/tmp/polling.log',
            sprintf("%s: (%d) (%s) %s\n", (new DateTime())->format(DATE_RFC822), getmypid(), join(') (', $args), $message),
            FILE_APPEND);
    }
}
