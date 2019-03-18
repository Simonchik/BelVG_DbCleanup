<?php

namespace BelVG\DbCleanup\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrdersDeleteCommand extends SymfonyCommand
{
    protected $state;
    protected $registry;
    protected $orderCollectionFactory;

    protected $bunch_size = 5;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->state = $state;
        $this->registry = $registry;
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('belvg:dbcleanup:imported_orders_delete')
            ->setDescription('Delete imported customers from DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->registry->register('isSecureArea', true);

        $i = 1;
        do {
            $io->success('Bunch #' . $i);
            $collection = $this->orderCollectionFactory->create();
            $collection
                ->setPageSize($this->bunch_size)
                ->setCurPage($i++);

            foreach ($collection as $order) {
                try {
                    if ($order->delete()) {
                        $io->success('Successfully deleted order #' . $order->getId());
                    } else {
                        $io->caution('Can not delete order #' . $order->getId());
                    }
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                }
            }
        } while ($collection->getSize());


        $io->success('Done');
        exit;
    }
}
