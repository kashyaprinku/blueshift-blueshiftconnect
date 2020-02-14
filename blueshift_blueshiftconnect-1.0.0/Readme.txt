#Installation

This module tested with Magento ver. 2.3.2 only.

Magento2 Blueshift Connect module installation is very easy, please follow the steps for installation-

1. Unzip the respective extension zip and then move "app/code" folder into magento root directory.

Run Following Command via terminal
-----------------------------------

php bin/magento setup:upgrade

php bin/magento setup:di:compile

php bin/magento setup:static-content:Deploy -f

sudo chmod -R 777 var/ generated/

sudo chmod -R 777 pub

php bin/magento cache:clean

php bin/magento cache:flush