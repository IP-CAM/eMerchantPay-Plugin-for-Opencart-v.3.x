emerchantpay Gateway Module for OpenCart
========================================

This is a Payment Module for OpenCart, that gives you the ability to process payments through emerchantpay's Payment Gateway - Genesis.

Requirements
------------

* OpenCart 3.0.X - 3.0.3.X (due to architectural changes, this module is __incompatible__ with OpenCart 1.X and 2.0.X)
* [GenesisPHP v1.18.4](https://github.com/GenesisGateway/genesis_php/tree/1.18.4) - (Integrated in Module)
* PCI-certified server in order to use ```emerchantpay Direct```

GenesisPHP Requirements
------------

* PHP version 5.3.2 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)

Installation via Extension Installer
------------
1.	Download the __emerchantpay Payment Gateway__, extract the contents of the folder
2.	Create a compressed ```zip``` file of the folder ```upload``` with name ```emerchantpay.ocmod.zip``` (excluding ```README.md```)
3.	Login inside the __OpenCart Admin Panel__
4.	Navigate to ```Extensions -> Installer``` and click on button ```Upload``` and choose the ```zip``` file ```emerchantpay.ocmod.zip```
5.	Navigate to ```Extensions -> Payments``` and click install on ```emerchantpay Direct``` and/or ```emerchantpay Checkout```
6.	Set the login credentials (```Username```, ```Password```, ```Token```) and adjust the configuration to your needs.

Installation (Manual)
------------

1.  Upload the contents of the folder ```upload``` (excluding ```README.md```) to the ```<root>``` folder of your OpenCart installation
2.  Login inside the __OpenCart Admin Panel__
3.  Navigate to ```Extensions -> Payments``` and click install on ```emerchantpay Direct``` and/or ```emerchantpay Checkout```
4.  Set the login credentials (```Username```, ```Password```, ```Token```) and adjust the configuration to your needs.

Enable OpenCart SSL
------------
This steps should be followed if you wish to use the ```emerchantpay Direct``` Method.

* Ensure you have installed a valid __SSL Certificate__ on your __PCI-DSS Certified__ Web Server and you have configured your __Virtual Host__ properly.
* Login to your OpenCart Admin Panel
* Navigate to ```Settings``` -> ```your Store``` -> ```Server```
* Set ```Use SSL``` to __Yes__ in ```Security``` tab and save your changes
* Set the __HTTPS_SERVER__ and __HTTPS_CATALOG__ settings in your ```admin/config.php``` to use ```https``` protocol
* Set the __HTTPS_SERVER__ setting in your ```config.php``` to use ```https``` protocol
* Set the __site_ssl__ setting to ```true``` in your ```system/config/default.php``` file
* It is recommended to add a __Rewrite Rule__ from ```http``` to ```https``` or to add a __Permanent Redirect__ to ```https``` in your virtual host

Recurring Payments
------------
OpenCart has an integrated functionality for processing recurring transactions.
In order to simplify the process of handling recurring payments, the recurring products cannot be ordered along with other products (recurring or non-recurring) and must be ordered separately, one per order.

If you are interested in, you could read more about:

* Total Order Calculation
* Payment Method Configuration
* Recurring Profile Creation
* Setting up Scheduled Tasks & Cron Jobs Configurations
* Cron Jobs IP Restrictions
* Handling Recurring Payments with Payment Module

in [wiki for Recurring Payments](https://github.com/emerchantpay/opencart-emp-plugin/wiki/OpenCart-Recurring-Module-Configurations)

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

[support]: mailto:tech-support@emerchantpay.net
