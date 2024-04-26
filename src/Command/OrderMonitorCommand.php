<?php

declare(strict_types=1);

namespace Twint\Command;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twint\Service\PaymentService;
use Twint\TwintPayment;

class OrderMonitorCommand extends Command
{
    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * OrderMonitorCommand constructor.
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('twint:order-monitor:scan');
        $this->setDescription('Scan all pending orders for checking');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->info('Start to scan all pending orders for checking');
        $pendingOrders = $this->paymentService->getPendingOrders();
        if(count($pendingOrders) > 0){
            $style->info(''.sprintf("These total: %d orders are going to process soon !", count($pendingOrders)));
            foreach($pendingOrders as $order){
                if(is_array($order)){
                    if(!empty($order[2])){
                        $order[2] = "<error>".$order[2]."</error>";
                    }
                    $style->text(str_pad($order[0],17)."  ".$order[1]."  ".$order[2]);
                }
                $style->info('Process for order '.$order->getOrderNumber());
                try{
                    $order = $this->paymentService->checkOrderStatus($order);
                    if($order){
                        $style->success('Update order '.$order->getOrderNumber().' successful!');
                    }
                }
                catch (\Exception $e) {
                    $style->error("Could not update the order status:". $e->getMessage() . 'Error Code:' . $e->getCode());
                }
            }


        }
        return TwintPayment::EXIT_CODE_SUCCESS;
    }
}
