# Smart2Pay Magento 2.1.x/2.2.x

Smart2Pay Plugin for Magento 2.1.x/2.2.x Shopping Cart

Tested on Magento 2.1.15, 2.2.5 and 2.2.6.

# Manual installation notes

1. If you manually install Smart2Pay Magento 2.x module copy _Smart2Pay_ directory to _{Magento_root}/app/code_ directory. If you don't have a _code_ directory in _{Magento_root}/app/_, create it first.

2. Run _composer require smart2pay/sdk-php_ in _{Magento_root}_ directory (not in module's directory).

3. Run _bin/magento setup:upgrade_ and _bin/magento setup:di:compile_ in _{Magento_root}_ directory.

4. Login in admin area of your Magento and configure the plugin.

If you have problems with any version please let us know your Magento, Smart2Pay module and Smart2Pay SDK versions at support@smart2pay.com.
