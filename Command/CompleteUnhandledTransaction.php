<?php


namespace Satispay\Satispay\Command;

use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class CompleteUnhandledTransaction extends Command
{

    protected $finalizeUnhandledOrdersService;

    protected $appState;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Satispay\Satispay\Model\FinalizeUnhandledOrders $finalizeUnhandledOrdersService,
        \Magento\Framework\App\State $appState,
        LoggerInterface $logger
    ) {
        $this->finalizeUnhandledOrdersService = $finalizeUnhandledOrdersService;
        $this->appState = $appState;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('satispay:process:unhandled')
            ->setDescription('Process unhandled Satispay Orders');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_FRONTEND);
            $this->finalizeUnhandledOrdersService->finalizeUnhandledOrders();
        } catch (\Exception $e) {
            $this->logger->error("An error has occured when Finalizing unhandled Satispay Orders: " . $e->getMessage());
        }
    }

    public function executeCron()
    {
        try {
            $this->finalizeUnhandledOrdersService->finalizeUnhandledOrders();
        } catch (\Exception $e) {
            $this->logger->error("An error has occured when Finalizing unhandled Satispay Orders: " . $e->getMessage());
        }
    }
}
