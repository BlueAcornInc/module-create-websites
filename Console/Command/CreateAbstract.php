<?php
/**
 * @package     BlueAcorn\CreateWebsites
 * @version     1.0.11
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2018 Blue Acorn, Inc.
 */

namespace BlueAcorn\CreateWebsites\Console\Command;

error_reporting('E_ALL & ~E_NOTICE');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CreateAbstract
 * @package BlueAcorn\CreateWebsites\Console\Command
 */
class CreateAbstract extends Command
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * CreateAbstract constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryRepositoryInterface $categoryRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CategoryRepositoryInterface $categoryRepository
    ){
        $this->scopeConfig                  = $scopeConfig;
        $this->categoryRepository           = $categoryRepository;
        return parent::__construct();
    }
    /**
     * @param $key
     * @param null $store
     * @return mixed
     */
    public function getScopeConfig($key, $store = null)
    {
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORE, $store);

    }

    /**
     * @param array $params
     * @param null $type
     */
    public function echoMessage($params = [], $type = null)
    {

        switch ($type){
            case 'error':
                $divider     = "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
                $divider    .= "!!!!!!!!!!-----------------------   ERROR   --------------------------!!!!!!!!!!\n";
                $divider    .= "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";

                break;
            case 'website':
                $divider    = '*-----------------------  Website / Group / Store View  --------------------------------*';
                break;
            case 'reindex':
                $divider    = '*----------------------------------   Reindex   ----------------------------------------*';
                break;
            case 'store_view':
                $divider    = '*----------------------------------  Store View  ---------------------------------------*';
                break;
            default:
                $divider    = '*---------------------------------------------------------------------------------------*';
                break;
        }

        echo "\n $divider \n";
        foreach ($params as $key => $_value)
        {
            echo "$key: $_value \n";
        }
    }

    /**
     * @param $root_category_id
     * @return int
     * @throws NoSuchEntityException
     */
    public function validateRootCategoryId($root_category_id)
    {
        /** @var \Magento\Catalog\Api\CategoryRepositoryInterface $cat  */
        $checkCategory = $this->categoryRepository->get($root_category_id);
        // Make sure that the category ID is valid and it is a level 1 ( root category )
        if((int)$checkCategory->getId() && ((int)$checkCategory->getLevel() === 1))
        {
            return (int)$root_category_id;
        }else{
            throw new NoSuchEntityException(__('Root Category ID doesn\'t exist: %1', $root_category_id));
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public function createRandomCode($length = 12)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $code = '';
        // first part
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }
        // Second part
        $code .='_';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }
        // Now we should have something like abcdef_abcdef but much more random
        return $code;
    }

}