<?php
/**
 * @package     BlueAcorn\CreateWebsites
 * @version     1.0.5
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
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use BlueAcorn\CreateWebsites\Console\Command\CreateAbstract;
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
class Build extends CreateAbstract
{

    protected $connection;
    protected $category;
    protected $categoryRepository;
    protected $website;
    protected $productRepository;
    protected $state;
    protected $code;
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
     * @var array
     */
    protected $allProductIds    = [];
    protected $allCategoryIds   = [];
    protected $duplicateIds     = [];

    /**
     * Build constructor.
     * @param \Magento\Framework\App\State $state
     * @param CategoryInterface $category
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductInterface $product
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
                                \Magento\Framework\App\State $state,
                                WebsiteFactory $websiteFactory,
                                Website $websiteResourceModel,
                                Store $storeResourceModel,
                                Group $groupResourceModel,
                                StoreFactory $storeFactory,
                                GroupFactory $groupFactory,
                                ScopeConfigInterface $scopeConfig){

        $objectManager              = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource                   = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection           = $resource->getConnection();
        $this->state                = $state;
        $this->scopeConfig          = $scopeConfig;
        $this->websiteFactory       = $websiteFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->storeFactory         = $storeFactory;
        $this->groupFactory         = $groupFactory;
        $this->groupResourceModel   = $groupResourceModel;
        $this->storeResourceModel   = $storeResourceModel;

        // To avoid Area code not set: Area code must be set before starting a session.
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
	    parent::__construct($scopeConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = 'This command creates websites';
        $this->setName('blueacorn:createwebsites:build')->setDescription($description);
        // Next line will add a new required parameter to our script
        $this->addArgument('number_of_websites_to_create', InputArgument::REQUIRED, __('Type a number of websites'));
        $this->addArgument('root_category_id', InputArgument::REQUIRED, __('ID of the root category'));

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $number_of_websites_to_create = $input->getArgument('number_of_websites_to_create');
            $root_category_id = $input->getArgument('root_category_id');
            for($i = 1; $i <= $number_of_websites_to_create; $i++)
            {
                // get our random code for the websites and store view
                $this->code                 = $this->createRandomCode();
                /** @var \Magento\Store\Model\Website $website */
                $website = $this->websiteFactory->create();

                // Make sure its not used already
                if(!$website->getId()) {
                    // Save the  website
                    $_website = $this->saveWebsite($website);
                    /** @var \Magento\Store\Model\Group $group */
                    $group  = $this->groupFactory->create();
                    $_group = $this->saveGroup($_website, $group, $root_category_id);
                }

                /** @var  \Magento\Store\Model\Store $store */
                $store = $this->storeFactory->create();
                $store->load($this->code);
                // make sure its not used already
                if(!$store->getId()){
                    $this->saveStoreView($_website, $_group, $store);
                }
            }

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
     */
    private function saveWebsite($website)
    {
        try{
            $website->load($this->code);

                $website->setCode($this->code);
                $website->setName($this->code);
                // Not sure this is needed
                //$website->setDefaultGroupId(3);
                return $this->websiteResourceModel->save($website);

        }catch (\Exception $e){
            $this->echoMessage(['ERROR' => $e->getMessage()]);
            return;
        }
    }
    /**
     * Assign to new website
     * Also set Root Category
     * @param $website
     * @param $group
     * @param $root_category_id
     */
    private function saveGroup($website, $group, $root_category_id)
    {
        try{

            $group->setWebsiteId($website->getWebsiteId());
            $group->setName($this->code);
            $group->setRootCategoryId($root_category_id);
            //$group->setDefaultStoreId(3);
            return $this->groupResourceModel->save($group);
        }catch (\Exception $e){
            $this->echoMessage(['ERROR' => $e->getMessage()]);
            return;
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
     */
    private function saveStoreView($website, $group, $store)
    {
        try{
            $store->setCode($this->code);
            $store->setName($this->code);
            $store->setWebsite($website);
            $store->setGroupId($group->getId());
            $store->setData('is_active','1');
            return $this->storeResourceModel->save($store);
        }catch (\Exception $e){
            $this->echoMessage(['ERROR' => $e->getMessage()]);
        }

    }



}
