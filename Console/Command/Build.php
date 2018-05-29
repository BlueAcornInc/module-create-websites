<?php
/**
 * @package     BlueAcorn\CreateWebsites
 * @version     1.0.9
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2018 Blue Acorn, Inc.
 */

namespace BlueAcorn\CreateWebsites\Console\Command;

// Leaving in case we need it
//error_reporting('E_ALL & ~E_NOTICE');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\ResourceModel\Website;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;


/**
 * Class Build
 * @package BlueAcorn\CreateWebsites\Console\Command
 */
class Build extends \BlueAcorn\CreateWebsites\Console\Command\CreateAbstract
{

    protected $connection;
    protected $category;
    protected $categoryRepository;
    protected $website;
    protected $state;
    protected $code;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;
    /**
     * @var Website
     */
    private $websiteResourceModel;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var GroupFactory
     */
    private $groupFactory;
    /**
     * @var Group
     */
    private $groupResourceModel;
    /**
     * @var Store
     */
    private $storeResourceModel;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Flat\Processor
     */
    protected $_productFlatIndexerProcessor;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $_productPriceIndexerProcessor;

    /**
     * @var array
     */
    protected $allProductIds    = [];
    protected $allCategoryIds   = [];
    protected $duplicateIds     = [];
    protected $websiteIds       = [];

    /**
     * This is needed I am using some Magento core that used it, so please don't judge me
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Build constructor.
     * @param \Magento\Framework\App\State $state
     * @param WebsiteFactory $websiteFactory
     * @param Website $websiteResourceModel
     * @param Store $storeResourceModel
     * @param Group $groupResourceModel
     * @param StoreFactory $storeFactory
     * @param GroupFactory $groupFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        WebsiteFactory $websiteFactory,
        Website $websiteResourceModel,
        Store $storeResourceModel,
        Group $groupResourceModel,
        StoreFactory $storeFactory,
        GroupFactory $groupFactory,
        ScopeConfigInterface $scopeConfig,
        CategoryRepositoryInterface $categoryRepository){

        $objectManager                          = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource                               = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->_objectManager                   = $objectManager;
        $this->_productFlatIndexerProcessor     = $productFlatIndexerProcessor;
        $this->_productPriceIndexerProcessor    = $productPriceIndexerProcessor;
        $this->connection                       = $resource->getConnection();
        $this->state                            = $state;
        $this->scopeConfig                      = $scopeConfig;
        $this->websiteFactory                   = $websiteFactory;
        $this->websiteResourceModel             = $websiteResourceModel;
        $this->storeFactory                     = $storeFactory;
        $this->groupFactory                     = $groupFactory;
        $this->groupResourceModel               = $groupResourceModel;
        $this->storeResourceModel               = $storeResourceModel;
        $this->categoryRepository               = $categoryRepository;
        $this->_eventManager                    = $eventManager;
        $this->productRepository                = $productRepository;
        $this->searchCriteriaBuilder            = $searchCriteriaBuilder;
        $this->filterBuilder                    = $filterBuilder;
        try {
            // To avoid Area code not set: Area code must be set before starting a session.
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }catch (\Exception $e){
            return $e;
        }
        // pass in our scope and category?  maybe we can remove this?
        parent::__construct($scopeConfig, $categoryRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = 'This command creates websites';
        $this->setName('blueacorn:createwebsites:build')
            ->setDescription($description)
            ->setDefinition([
                new InputOption(
                    'websites',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Number of websites to create.'
                ),
                new InputOption(
                    'root-category-id',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'What is the root category id'
                )
            ]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            // Set our variable for number of websites to create
            $websites_to_create = (int)$input->getOption('websites');
            // Get the root category ID
            $root_category_id   = $this->validateRootCategoryId($input->getOption('root-category-id'));
            // Loop through all the websites to create
            for($i = 1; $i <= $websites_to_create; $i++)
            {
                // get our random code for the websites and store view
                $this->code                 = $this->createRandomCode();
                /** @var \Magento\Store\Model\Website $website */
                $website = $this->websiteFactory->create();

                // Make sure its not used already
                if(!$website->getId()) {
                    // Save the  website
                    $this->saveWebsite($website);
                    /** @var \Magento\Store\Model\Group $group */
                    $group  = $this->groupFactory->create();
                    $this->saveGroup($website, $group, $root_category_id);
                }

                /** @var  \Magento\Store\Model\Store $store */
                $store = $this->storeFactory->create();
                $store->load($this->code);
                // make sure its not used already
                if(!$store->getId()){
                    $this->saveStoreView($website, $group, $store);
                }

                $this->websiteIds[$website->getId()] = $website->getId();
                $this->echoMessage(['Code: ' => $this->code, 'Website ID' => $website->getId(), 'Group ID' => $group->getId(), 'Store View ID' => $store->getId(), 'Store View Name & Code' => $this->code, 'Root Category ID' => $root_category_id], 'website');
                // reset to ensure we don't get any bleed-over
                $this->code = null;
                $website    = null;
                $group      = null;
                $store      = null;
            }
            // We are ready to move all the products to our websites
            $this->saveProductsToNewlyCreatedWebsites();
        }catch (\Exception $e){
            $this->echoMessage(['Error during website/goup/store creation' => $e->getMessage()], 'error');
            return;
        }
    }



    /**
     * Name
     * Code
     * Optional sort_order
     * After complete setup website/store/store view
     *   website['default_group_id']
     * @param $website
     * @return $this|bool
     */
    /**
     * @param $website
     * @return $this|bool
     */
    private function saveWebsite($website)
    {
        try{
            $website->load($this->code);
            $website->setCode($this->code);
            $website->setName($this->code);
            // Not sure this is needed
            //$website->setDefaultGroupId(3);

            $this->websiteResourceModel->save($website);

            return $this;

        }catch (\Exception $e){
            $this->echoMessage(['Error message' => $e->getMessage()], 'error');
            return false;
        }
    }
    /**
     * Assign to new website
     * Also set Root Category
     * @param $website
     * @param $group
     * @param $root_category_id
     * @return $this|bool
     */
    private function saveGroup($website, $group, $root_category_id)
    {
        try{

            $group->setWebsiteId($website->getWebsiteId());
            $group->setName($this->code);
            $group->setRootCategoryId($root_category_id);
            //$group->setDefaultStoreId(3);
            $this->groupResourceModel->save($group);
            return $this;
        }catch (\Exception $e){
            $this->echoMessage(['Error message' => $e->getMessage()], 'error');
            return false;
        }

    }

    /**
     * Assign to new store['group_id']
     * Name
     * Code
     * Status boolean 0=disabled 1=enabled
     * optional sort_order
     * @param $website
     * @param $group
     * @param $store
     * @return $this|bool
     */
    private function saveStoreView($website, $group, $store)
    {
        try{
            $store->setCode($this->code);
            $store->setName($this->code);
            $store->setWebsite($website);
            $store->setGroupId($group->getId());
            $store->setData('is_active','1');
            $this->storeResourceModel->save($store);
            return $this;
        }catch (\Exception $e){
            $this->echoMessage(['Error message' => $e->getMessage()], 'error');
            return false;
        }
    }

    /**
     * I borrowed the bulk of this logic from
     * vendor/magento/module-catalog/Controller/Adminhtml/Product/Action/Attribute/Save.php:88
     * In that you can associate products to websites
     *
     * @return $this
     */
    private function saveProductsToNewlyCreatedWebsites()
    {
        $productIds = $this->_getAllProductIds();
        // Count all the producdt IDs if they are 0, something is wrong, just return;
        if (!count($productIds)) {
            return $this;
        }

        /* Collect Data */
         $websiteAddData = $this->websiteIds;

        try {
            // This should always be set, but it doesn't hurt to have it, its simple validation
            if ($websiteAddData) {
                /* @var $actionModel \Magento\Catalog\Model\Product\Action */
                $actionModel = $this->_objectManager->get('Magento\Catalog\Model\Product\Action');
                // Update the websites
                $actionModel->updateWebsites($productIds, $websiteAddData, 'add');

                // This may be removed, not sure, Magento core was doing this
                $this->_eventManager->dispatch('catalog_product_to_website_change', ['products' => $productIds]);
            }
            $this->echoMessage(['Success' => __('A total of %1 record(s) were updated.', count($productIds))]);

            // simple validation
            if (!empty($websiteAddData)) {
                // Reindex Product
                $this->_productFlatIndexerProcessor->reindexList($productIds);
                // Reindex product price
                $this->_productPriceIndexerProcessor->reindexList($productIds);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->echoMessage(['Localized Exception Error message' => $e->getMessage()], 'error');
        } catch (\Exception $e) {
            $this->echoMessage(['Exception Error message' => $e->getMessage()], 'error');
        }
    }
    /**
     * We just want an empty filter for now
     * @return \Magento\Framework\Api\Filter[]
     */
    private function buildFilters()
    {
        $filters = [];
        return $filters;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function buildSearchCriteria()
    {

        $this->searchCriteriaBuilder->setFilterGroups([]);
        return $this->searchCriteriaBuilder->addFilter('entity_id', '1', 'gteq')->create();
    }
    /**
     * Get all the product IDs from this site
     * @return array
     */
    protected function _getAllProductIds()
    {
        // setup our array
        $productIds     = [];

        // Get all product IDs
        $searchCriteria = $this->buildSearchCriteria();
        $product_list   = $this->productRepository->getList($searchCriteria);

        // Loop through all the products and assign them to our array
        foreach ($product_list->getItems() as $_product){
            // assign to our array
            $productIds[] = $_product->getId();
        }
        // Return our array
        return $productIds;
    }

}
