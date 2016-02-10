# Magento LightSpeed Module v2

LTS2 is an advanced FPC cache engine for Magento 1.x with nested blocks capability.

## Getting started

Just copy in your Magento root and turn on for "System > Cache Management" ;)

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
