## Smart2Pay Magento 2.1.x/2.2.x/2.3.x

Smart2Pay Plugin for Magento 2.1.x/2.2.x/2.3.x Shopping Cart

Tested on Magento 2.1.15, 2.2.5, 2.2.6 and 2.3.4.

#### Composer installation (recommended)

1. Install Smart2Pay Magento plugin using composer:

```shell script
composer require smart2pay/magento2
```

2. Follow steps from __Magento setup__ section.

3. Login in admin area of your Magento shop and configure the plugin.

#### Manual installation notes

1. Copy Smart2Pay plugin files:

    1.1 Zip file
    
    Download zip file [from Smart2Pay Magento 2 releases](https://github.com/Smart2Pay/magento20/releases) and unzip it in a temporary location on your Magento 2 server.
    
    1.2 git clone
    
    Execute ```git clone https://github.com/Smart2Pay/magento2``` in a temporary directory on your Magento 2 server.

2. In _{Magento_root}/app/code_ directory create _Smart2Pay/GlobalPay_ directories. If you don't have a _code_ directory in _{Magento_root}/app/_, create it first.

3. Copy plugin files from the temporary directory created at step 1 in _{Magento_root}/app/code/Smart2Pay/GlobalPay_ directory.
 
4. To check if the files were copied correctly, check if there is a _registration.php_ file in _{Magento_root}/app/code/Smart2Pay/GlobalPay_ directory.

5. Run ```composer require smart2pay/sdk-php``` in _{Magento_root}_ directory (not in module's directory).

6. Complete steps from __Magento setup__ section.

7. Login in admin area of your Magento and configure the plugin.

If you have problems with any version please let us know your Magento, Smart2Pay module and Smart2Pay SDK versions at support@smart2pay.com.

#### Magento setup 

1. Make sure plugin is enabled: ```bin/magento module:enable Smart2Pay_GlobalPay```.

2. Run following commands:

```shell script
bin/magento setup:upgrade
bin/magento setup:di:compile
```
