# Project Freight Train

Project Freight Train is a configurable rate calculator for freight services in Eve Online. It features a suite of configuration options, including standard routes, rush hauling, both broad and specific routing restrictions, custom volume and collateral limitations in a variety of scopes, and different pricing schemes for Jump Drive, Gate, Wormhole, and Pochven based hauling methods. 

**Current Version: Pickup – 0 – 0**

## Requirements

The core of this framework requires the following:

* Apache ≥ 2.4
  * The `DocumentRoot` config option to set `/public`
  * The `FallbackResource` config option set to `/index.php`
* PHP ≥ 8.0
  * The `curl` Built-In Extension
  * The `pdo_mysql` Built-In Extension
  * The `openssl` Built-In Extension
* Python ≥ 3.11
  * [Python MySQL Connector](https://dev.mysql.com/downloads/connector/python/)
* An SQL Server
  * If you are using MySQL, the Authentication Method **MUST** be the Legacy Version. PDO does not support the use of `caching_sha2_password` Authentication.
* A Registered Eve Online Application with the `esi-search.search_structures.v1` scope.
  * This can be setup via the [Eve Online Developers Site](https://developers.eveonline.com/).
* [When Using The Neucore Authentication Method] A Neucore Application
  * The application needs the `app-chars` and `app-groups` roles added, along with any groups that you want to be able to set access roles for.

## Setup

* Rename the Configuration File in `/config/config.ini.dist` to `/config/config.ini` and setup as needed.
  * If you need to move this file, you'll need to change the path it's accessed from in `/config/config.php` and `/scripts/Python/initialSetup.py`
* Rename `/config/frontPage.html.dist` to `/config/frontPage.html` and customize as desired.
* Access the webserver at least once to initialize the database.
* Run `/scripts/Python/initialSetup.py` to populate the database with static information about Eve's geography as well as default routing options.

## Using Environment Variables Instead of a Config File

* You can find environment variable keys associated with each config value in the comments of `/config/config.ini.dist`.
* Some variables are required, some have defaults, and some are only needed in specific circumstances. These are listed in the comments of the file.
* The web app and python scripts each only support either Environment Variables or a Config File, not both.
  * The Config File always takes priority. To use Environment Variables, delete `/config/config.ini` if it exists.