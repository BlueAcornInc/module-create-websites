# Blue Acorn Create Websites for Magento 2

Create websites via a command line

##### Run this command to add to your composer.json
    
    composer require blueacorn/module-create-websites:1.0.*
    
##### It should look somethig like this afterwards
    "require": {
       ...
       "blueacorn/module-create-websites": "1.0.*"
       ...
    }
 
## Sample commands
    
### Full process, just define how many websites you want and what is the root category id    
    php bin/magento blueacorn:createwebsites:build --websites=10 --root-category-id=2
    
### To avoid having the product indexer run add the optional --run-product-indexer=no
    php bin/magento blueacorn:createwebsites:build --websites=1 --root-category-id=2 --run-product-indexer=no 
## Dependencies
none

### Release
* 1.0 Initial push
* 1.0.0 Added etc/
* 1.0.1 First attempt to install
* 1.0.2 Fixed command, and moved README.md to docs folder
* 1.0.3 Updated readme and trying to fix upgrade 
* 1.0.4 Added number of store requirement
* 1.0.6 Added default category requirement.  Create websites store groups and store views.
* 1.0.7 Added better flags.  Fixed some logic when creating store groups and store views.
* 1.0.8 Added category validation check
* 1.0.9 Fixing the abstract class to handle the validation of the root category ID.  Adding all products to newly created websites 
* 1.0.10 Adding composer instructions in README.md
* 1.0.11 Added some instructions on the README.md
* 1.0.12 Updated the README.md with notes on how to install the module.