# README #

### Openprovider WHMCS SSL Bundle ###

This repository contains addon and server module for SSL part of Openprovider.

Version: 2.0.0

### Dependencies ###

Please check out this list:

* PHP 5.4+
* https://getcomposer.org/

### Module installation ###

1. Copy content of your module folder to `/var/www/whmcs/modules/servers/<your_provisioning_module>` folder.
2. Run `composer update && composer du` in copied folder.
3. `your_provisioning_module` is the folder name of your addon, e.g. `/var/www/whmcs/modules/servers/openprovidersslnew`

### Addon installation ###

1. Copy content of your addon folder to `/var/www/whmcs/modules/addons/<your_addon_name>` folder and that's it.
2. `your_addon_name` is the folder name of your addon, e.g. `/var/www/whmcs/modules/addons/openproviderssl_new`

### How to install redirect script to the SSL Panel for end-user order access ###

1. Go to the WHMCS root directory, e.g. /var/www/whmcs
2. Copy here file from root of the repo: generateSslPanelOtpToken.php

### Contribution guidelines ###

* If you want to write tests, run `composer update` at the root of repository.
* Code review is made by owner of this repository for every pull request.
* Please follow PSR and other good coding standards.

### Who do I talk to? ###

* Yaroslav Lukyanov <ylukyanov@openprovider.ru>