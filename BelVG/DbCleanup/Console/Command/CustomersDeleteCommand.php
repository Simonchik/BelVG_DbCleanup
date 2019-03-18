<?php

namespace BelVG\DbCleanup\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomersDeleteCommand extends SymfonyCommand
{
    protected $state;
    protected $registry;
    protected $customerCollectionFactory;

//    protected $bunch_size = 5;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->state = $state;
        $this->registry = $registry;
        $this->customerCollectionFactory = $customerCollectionFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('belvg:dbcleanup:imported_customers_delete')
//            ->addArgument('max_customer_id', InputArgument::OPTIONAL, 'Customer ID (Maximum customer ID that will be deleted)')
            ->setDescription('Delete imported customers from DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

//        $max_product_id = $input->getArgument('max_product_id');
//        if (!$max_product_id) {
//            $io->caution('Please pass the `max_product_id` param');
//            exit;
//        }

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->registry->register('isSecureArea', true);

        $collection =  $this->customerCollectionFactory->create();
        foreach ($collection as $customer) {
            try {
                if ($customer->delete()) {
                    $io->success('Successfully deleted customer #' . $customer->getId());
                } else {
                    $io->caution('Can not delete customer #' . $customer->getId());
                }
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        $io->success('Done');
        exit;
    }
}
