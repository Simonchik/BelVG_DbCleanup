<?php

namespace BelVG\DbCleanup\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductsDeleteCommand extends SymfonyCommand
{
    protected $state;
    protected $registry;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $productCollectionFactory;
    protected $filterBuilder;
    protected $filterGroupBuilder;

    protected $bunch_size = 5;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->state = $state;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('belvg:dbcleanup:imported_products_delete')
            ->addArgument('max_product_id', InputArgument::OPTIONAL, 'Product ID (Maximum product ID that will be deleted)')
            ->setDescription('Delete imported products from DB');
    }

    protected function getProducts($max_product_id, $currentPage = 1)
    {
        $filterByMaxProductId = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('lteq')
            ->setValue($max_product_id)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$filterByMaxProductId])
            ->setCurrentPage($currentPage)
            ->setPageSize($this->bunch_size)
            ->create();

        return $this->productRepository->getList($searchCriteria)->getItems();
    }

    protected function deleteProductsWithNullSku()
    {
        $collection =  $this->productCollectionFactory->create();
        $collection->getSelect()->where('`sku` IS NULL');
        foreach ($collection as $product) {
            $product->delete();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $max_product_id = $input->getArgument('max_product_id');
        if (!$max_product_id) {
            $io->caution('Please pass the `max_product_id` param');
            exit;
        }

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->registry->register('isSecureArea', true);

        //workaround for products that can't be deleted using Repository
        $this->deleteProductsWithNullSku();

        $i = 1;
        do {
            $productList = $this->getProducts($max_product_id, $i++);
            foreach ($productList as $product) {
                try {
//                    if (empty($product->getSku())) {
//                        $io->caution('Empty SKU #' . $product->getId());
//                        return;
//                    }

                    if ($this->productRepository->delete($product)) {
                        $io->success('Successfully deleted #' . $product->getId() . '; sku: ' . $product->getSku());
                    } else {
                        $io->caution('Can not delete #' . $product->getId() . '; sku: ' . $product->getSku());
                    }
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                }
            }
            unset($product);

            $io->success('---------------------- Iteration end #' . $i);

        } while (count($productList));

        $io->success('Done');
        exit;
    }
}
