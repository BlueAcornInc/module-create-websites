<?php
/**
 * @package     BlueAcorn\CreateWebsites
 * @version     1.0.3
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2018 Blue Acorn, Inc.
 */

namespace BlueAcorn\CreateWebsites\Console\Command;

// Leaving in case we need it
//error_reporting('E_ALL & ~E_NOTICE');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use BlueAcorn\CreateWebsites\Console\Command\CreateAbstract;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Build
 * @package BlueAcorn\CreateWebsites\Console\Command
 */
class Build extends CreateAbstract
{

    protected $connection;
    protected $category;
    protected $categoryRepository;
    protected $product;
    protected $productRepository;
    protected $state;

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
                                CategoryInterface $category,
                                CategoryRepositoryInterface $categoryRepository,
                                ProductInterface $product,
                                ProductRepositoryInterface $productRepository,
                                ScopeConfigInterface $scopeConfig){

        $objectManager              = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource                   = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection           = $resource->getConnection();
        $this->state                = $state;
        $this->scopeConfig                  = $scopeConfig;
        $this->category             = $category;
        $this->categoryRepository   = $categoryRepository;
        $this->product              = $product;
        $this->productRepository    = $productRepository;

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
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            for($i = 1; $i <= 10; $i++)
            {
                $this->saveWebsite($i);
            }

        }catch (\Exception $e){
            $this->echoMessage(['Error during Products URL Rewrites delete' => $e->getMessage()], 'error');

        }

    }

    /**
     * @param $i
     */
    private function saveWebsite($i)
    {
        echo $this->echoMessage(['stuff'=> $i . 'is currently running']);
        return;
    }


}
