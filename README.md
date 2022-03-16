# magento-plugin
Plugin de magento para checkout

# D-una Plugin setup

 ## Go to the project app/code folder and create the new below new module folder DIR.
 ## Module folder DIR - DUna/Payments
 ## clone GIP repository inside the module "Payments" DIR.

 ## After run below magento setup command.

   ### sudo chmod -R 777 var var/* generated/ generated/* pub pub/static/*
   ### php bin/magento setup:upgrade && php bin/magento s:d:c && php bin/magento setup:static-content:deploy -f && php bin/magento cache:flush