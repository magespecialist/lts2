# Magento LightSpeed Module v2

LTS2 is an advanced FPC cache engine for Magento 1.x with nested blocks capability.

## How to install

* First of all you have to turn off your Magento cache (very important)
* Copy this module in your Magento root
* Turn on your cache by clicking "refresh"
 
If you get an error empty your cache manually:

In standard installations you must clear the content of var/cache (be careful, do not remove the folder itself).
If you have another cache engine like redis or memcache you will have to flush them or restart.

## How to uninstall

Just remove the files ;)
Remember to remove app/code/local/Mage/Core/Block/Abstract.php

## Configuring the cache

The LTS2 module configuration is entirely PHP driven for the best performance and flexibility.
All the configration parameters are inside MSP_LTSR2_Model_Cache class.

You can configure LTS2 by modifying two class methods: _getActionCacheData and _getBlockCacheData .

Each method must return one PHP hash containing the following keys:
* type: How to handle the item (HANDLING_CACHE or HANDLING_NOCACHE)
* keys: Cache key (different keys means different cached version of this item)
* lifetime: Cache expire time in seconds
* tags: Magento cache tags

### _getActionCacheData method:

Defines caching options depending on the action.

### _getBlockCacheData method:

Defines block caching options on cached actions.
