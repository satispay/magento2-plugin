<?php


namespace Satispay\Satispay\Command;

use Magento\Framework\App\Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteUnhandledTransaction extends Command
{

    protected $finalizeUnhandledOrdersService;

    protected $appState;

    public function __construct(
        \Satispay\Satispay\Model\FinalizeUnhandledOrders $finalizeUnhandledOrdersService,
        \Magento\Framework\App\State $appState
    ) {
        $this->finalizeUnhandledOrdersService = $finalizeUnhandledOrdersService;
        $this->appState = $appState;
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
        $this->appState->setAreaCode(Area::AREA_FRONTEND);
        $this->finalizeUnhandledOrdersService->finalizeUnhandledOrders();
    }

    public function executeCron()
    {
        $this->finalizeUnhandledOrdersService->finalizeUnhandledOrders();
    }
}
