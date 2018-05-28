<?php
/**
 * @package     BlueAcorn\CreateWebsites
 * @version     1.0.7
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
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ){
        $this->scopeConfig                  = $scopeConfig;
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
                $divider     = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';
                $divider    .= '
!!!!!!!!!!-----------------------   ERROR   --------------------------!!!!!!!!!!';
                $divider    .= '
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';

                break;
            case 'url_rewrite':
                $divider    = '*-------------------------  Product and url_rewrite  ---------------------------------*';
                break;
            case 'category':
                $divider    = '*----------------------------------  Category  ---------------------------------------*';
                break;
            default:
                $divider    = '*-------------------------------------------------------------------------------------*';
                break;
        }

        echo '
'. $divider.'
';
        foreach ($params as $key => $_value)
        {
            echo $key . ': ' . $_value .'
';
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