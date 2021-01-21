<?php
/**
 * IDEALIAGroup srl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.idealiagroup.com/magento-ext-license.html
 *
 * @category   MSP
 * @package    MSP_LTS2
 * @copyright  Copyright (c) 2013 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license    http://www.idealiagroup.com/magento-ext-license.html
 */

class MSP_LTS2_Model_Cache
{
    const CACHE_STATUS_MISS = 'miss';
    const CACHE_STATUS_HIT = 'hit';

    protected $_cacheTag = 'MSP_LIGHTSPEED2';
    protected $_tmpBlock = array();
    protected $_involvedBlocks = array();

    protected $_blockCacheStatus = array();
    protected $_layoutCacheStatus = 'miss';
    protected $_actionCacheStatus = 'miss';

    protected $_initDone = false;

    /**
     * Get block placeholder
     * @param string $blockName
     * @return string
     */
    public function getBlockPlaceholder($blockName)
    {
        return '<msp_lts code="'.$blockName.'" />';
    }

    /**
     * Get cache block status
     * @return array
     */
    public function getCacheBlockStatus()
    {
        return $this->_blockCacheStatus;
    }

    /**
     * Get cache action status
     * @return string
     */
    public function getCacheActionStatus()
    {
        return $this->_actionCacheStatus;
    }

    /**
     * Get cache layout status
     * @return string
     */
    public function getCacheLayoutStatus()
    {
        return $this->_layoutCacheStatus;
    }

    /**
     * Set cache block status for debugging purpose
     * @param $blockName
     * @param $cacheStatus
     * @param $cacheKey
     * @return array
     */
    public function setCacheBlockStatus($blockName, $cacheStatus)
    {
        $cacheKey = $this->getBlockCacheKey($blockName);

        $this->_blockCacheStatus[$blockName] = array(
            'status' => $cacheStatus,
            'key' => $cacheKey,
        );

        return $this;
    }

    /**
     * Set cache action status for debugging purpose
     * @param $cacheStatus
     * @return array
     */
    public function setCacheActionStatus($cacheStatus)
    {
        $this->_actionCacheStatus = $cacheStatus;
    }

    /**
     * Set layout action status for debugging purpose
     * @param $cacheStatus
     * @return array
     */
    public function setCacheLayoutStatus($cacheStatus)
    {
        $this->_layoutCacheStatus = $cacheStatus;
    }
        
    /**
     * Get Magento core cache
     * @return Mage_Core_Model_Cache
     */
    protected function _getCoreCache()
    {
        $this->_init();
        return $this->_coreCache;
    }
    
    /**
     * Initialize cache
     * @return MSP_LTS2_Model_Cache
     */
    public function _init()
    {
        if ($this->_initDone) {
            return $this;
        }
    
        $this->_initDone = true;
        $this->_coreCache = Mage::app()->getCacheInstance();
    }
    
    /**
     * Return true if cache is active
     * @return boolean
     */
    public function isActive()
    {
        return Mage::helper('msp_lts2/cache')->isActive();
    }
    
    /**
     * Get a list of involved blocks
     * @return array
     */
    public function getInvolvedBlocksList()
    {
        return array_unique($this->_involvedBlocks);
    }
    
    /**
     * Add a block to involved list
     * @param string $blockName
     */
    public function addInvolvedBlock($blockName)
    {
        $this->setCacheBlockStatus($blockName, static::CACHE_STATUS_MISS);
        $this->_involvedBlocks[] = $blockName;
    }
    
    /**
     * Add temporary stored block
     * @param string $blockName
     * @param string $content
     */
    public function setTmpBlock($blockName, $content)
    {
        $this->addInvolvedBlock($blockName);
        $this->_tmpBlock[$blockName] = $content;
    }
    
    /**
     * Set dynamic block
     * @param string $blockName
     * @param string $content
     */
    public function setBlock($blockName, $content)
    {
        $this->_init();
        $this->setTmpBlock($blockName, $content);
        $blockCacheData = Mage::getSingleton('msp_lts2/cache')->getBlockCacheData($blockName);
        
        $key = $this->getBlockCacheKey($blockName);
        $this->setCacheItem($key, $content, $blockCacheData['tags'], $blockCacheData['lifetime']);
    }

    /**
     * Set dynamic block
     * @param string $content
     * @param array $blocks
     * @param array $handles
     * @return $this
     */
    public function setAction($content, $blocks, $handles)
    {
        $this->_init();
    
        $actionCacheData = Mage::getSingleton('msp_ltsr2/cache')->getActionCacheData();
        $key = $this->getActionCacheKey();
    
        $this->setCacheItem($key, $content, $actionCacheData['tags'], $actionCacheData['lifetime'], array(
            'blocks' => $blocks,
            'handles' => $handles,
        ));
        return $this;
    }

    /**
     * Return block content
     * @param string $blockName
     * @return NULL|string
     */
    public function getBlock($blockName)
    {
        $this->_init();

        $this->addInvolvedBlock($blockName);

        if (isset($this->_tmpBlock[$blockName])) {
            return $this->_tmpBlock[$blockName];
        }
    
        $key = $this->getBlockCacheKey($blockName);
        if (!$this->cacheItemExists($key)) {
            $layout = Mage::getSingleton('msp_lts2/layout');
            $layout->loadLayout();

            $html = '';
            $block = $layout->getLayout()->getBlock($blockName);

            if ($block) {
                $html = $block->toHtml();
            }
            return $html;
        }

        $this->setCacheBlockStatus($blockName, static::CACHE_STATUS_HIT);
        return $this->getCacheItem($key);
    }
    
    /**
     * Get action content
     * @return string
     */
    public function getAction()
    {
        $this->_init();
        $key = $this->getActionCacheKey();
    
        return $this->getCacheItem($key);
    }

    /**
     * Get action block names
     * @return array
     */
    public function getActionBlocks()
    {
        $this->_init();
        $key = $this->getActionCacheKey();

        $res = $this->getCacheMeta($key, 'blocks');
        return is_array($res) ? $res : array();
    }

    /**
     * Get cached layout handlers
     * @return array
     */
    public function getActionLayoutHandles()
    {
        $this->_init();
        $key = $this->getActionCacheKey();

        $res = $this->getCacheMeta($key, 'handles');
        return is_array($res) ? $res : array();
    }
    
    /**
     * Get non cached blocks
     * @return array
     */
    public function getActionNonCachedBlocks()
    {
        $list = $this->getActionBlocks();
        
        $return = array();
        foreach ($list as $blockName) {
            $key = $this->getBlockCacheKey($blockName);
            if ($this->cacheItemExists($key)) {
                continue;
            }
            $return[] = $blockName;
        }
        
        return $return;
    }
    
    /**
     * Return cache item
     * @param string $key
     * @return NULL|string
     */
    public function getCacheInfo($key)
    {
        $this->_init();
        $res = $this->_getCoreCache()->load($key);
        
        if (!$res) {
            return null;
        }
        return unserialize($res);
    }
    
    /**
     * Return cache item
     * @param string $key
     * @return NULL|string
     */
    public function getCacheMeta($key, $meta)
    {
        $res = $this->getCacheInfo($key);
        if (!$res || !isset($res[$meta])) {
            return null;
        }
        
        return $res[$meta];
    }
    
    /**
     * Return cache item
     * @param string $key
     * @return NULL|string
     */
    public function getCacheItem($key)
    {
        return $this->getCacheMeta($key, 'content');
    }
    
    /**
     * Set cache item content
     * @param string $key
     * @param string $content
     * @param array $tags
     * @param int $lifetime
     * @return MSP_LTS2_Model_Cache
     */
    public function setCacheItem($key, $content, array $tags = array(), $lifetime = 86400, $meta = array())
    {
        $tags[] = $this->_cacheTag;
    
        $content = array('content' => $content, 'ts' => time());
        foreach ($meta as $k => $v) {
            $content[$k] = $v;
        }
        
        $content = serialize($content);
        
        $this->_init();
        $this->_getCoreCache()->save($content, $key, $tags, $lifetime);
        return $this;
    }
    
    /**
     * Invalidate cache content
     * @param string $key
     * @return MSP_LTS2_Model_Cache
     */
    public function invalidateCacheItem($key)
    {
        $this->_init();
        $this->_getCoreCache()->remove($key);
        return $this;
    }
    
    /**
     * Return
     * @param string $modelInstance
     * @return MSP_LTS2_Model_Cache
     */
    public function invalidateOnModelSave($modelInstance)
    {
        $invalidations = Mage::getSingleton('msp_ltsr2/cache')->getBlocksModelInvalidation($modelInstance);
        $tags = array();

        if ($modelInstance instanceof Mage_CatalogInventory_Model_Stock_Item) {
            $parentIds = Mage::getSingleton('catalog/product_type_configurable')
                ->getParentIdsByChild($modelInstance->getProductId());
            foreach($parentIds as $parentId) {
                $tags[] = 'CATALOG_PRODUCT_' . $parentId;
            }
        }

        foreach ($invalidations['global'] as $blockName) {
            $tags[] = 'MSP_BLOCK_NAME_'.$blockName;
        }

        foreach ($invalidations['session'] as $blockName) {
            $tags[] = 'MSP_BLOCK_SESSION_'.$blockName.'_'.Mage::helper('msp_lts2')->getSessionCode();
        }

        if (count($tags)) {
            $this->_init();
            $this->_getCoreCache()->clean($tags);
        }
        
        return $this;
    }
    
    /**
     * Check if item is in cache
     * @param string $key
     * @return boolean
     */
    public function cacheItemExists($key)
    {
        $this->_init();
        return $this->_getCoreCache()->load($key) ? true : false;
    }
    
    /**
     * Get cache key for block
     * @param string $blockName
     * @return array
     */
    public function getBlockCacheData($blockName)
    {
        return Mage::getSingleton('msp_ltsr2/cache')->getBlockCacheData($blockName);
    }
    
    /**
     * Get cache key for block
     * @param string $blockName
     * @return string
     */
    public function getBlockCacheKey($blockName)
    {
        $data = $this->getBlockCacheData($blockName);
        return 'msp_lts_block_'.md5(implode('::', $data['keys']));
    }
    
    /**
     * Return block cache type
     * @param string $blockName
     * @return string
     */
    public function getBlockCacheHandling($blockName)
    {
        $data = $this->getBlockCacheData($blockName);
        if (!is_array($data) || !isset($data['type'])) {
            return MSP_LTSR_Model_Cache::HANDLING_NOCACHE;
        }
    
        return $data['type'];
    }
    
    /**
     * Get cache information for action
     * @return array
     */
    public function getActionCacheData()
    {
        return Mage::getSingleton('msp_ltsr2/cache')->getActionCacheData();
    }
    
    /**
     * Get cache key for action
     * @return string
     */
    public function getActionCacheKey()
    {
        $data = $this->getActionCacheData();
        return 'msp_lts_action_'.md5(implode('::', $data['keys']));
    }
    
    /**
     * Return block cache type
     * @return string
     */
    public function getActionCacheHandling()
    {
        $data = $this->getActionCacheData();
        if (!is_array($data) || !isset($data['type'])) {
            return MSP_LTSR_Model_Cache::HANDLING_NOCACHE;
        }
    
        return $data['type'];
    }
    
    /**
     * Check if can cache action
     * return boolean
     */
    public function canCacheAction()
    {
        /* @var $rules MSP_LTSR2_Model_Cache */
        if ($this->getActionCacheHandling() == MSP_LTS2_Model_Rule_Cache::HANDLING_NOCACHE) {
            return false;
        }
    
        return true;
    }

    /**
     * Replace form keys
     * @param string $body
     * @return string
     */
    public function replaceFormKeys($body)
    {
        $formKey = Mage::getSingleton('core/session')->getFormKey();
        $body = preg_replace('/\/form_key\/(.+?)\//', '/form_key/'.$formKey.'/', $body);

        return $body;
    }

    /**
     * Replace placeholders inside body
     * @param string $body
     * @return string
     */
    public function replacePlaceholders($body)
    {
        if (!$body) {
            return $body;
        }
    
        // Fetch block names
        if (!preg_match_all('/<msp_lts code="'.'([\w\.\-\_]+)" \/>/', $body, $matches)) {
            return $body;
        }

        $blockNames = $matches[1];

        // Replace cached blocks
        $replacements = array(
            'placeholders' => array(),
            'html' => array(),
        );
        foreach ($blockNames as $blockName) {
            $content = $this->replacePlaceholders($this->getBlock($blockName));
            $replacements['placeholders'][] = $this->getBlockPlaceholder($blockName);
            $replacements['html'][] = $content;
        }

        $body = str_replace($replacements['placeholders'], $replacements['html'], $body);
        return $this->replacePlaceholders($body);
    }
}
