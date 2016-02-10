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

abstract class MSP_LTS2_Model_Rule_Cache
{
	const HANDLING_NOCACHE = 'nocache'; // Always dynamic, do not cache it at all
	const HANDLING_CACHE = 'cache'; // Cacheable under defined circumstances
	const HANDLING_STATIC = 'static'; // Recursivley cache contents within cached section

	/**
	 * Return session key
	 * @return string
	 */
	protected function _getSessionCode()
	{
		return Mage::helper('msp_lts2')->getSessionCode();
	}

	/**
	 * Return userid (0 for not loggedin)
	 * @return int
	 */
	protected function _getUserId()
	{
		return Mage::helper('msp_lts2')->getUserId();
	}

	/**
	 * Return groupid (0 for not loggedin)
	 * @return int
	 */
	protected function _getGroupId()
	{
		return Mage::helper('msp_lts2')->getGroupId();
	}

	/**
	 * Return requested URL
	 * @return string
	 */
	protected function _getRequestedUrl()
	{
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * Check if model is instance or subinstance
	 * @param object $modelInstance
	 * @param string $modelName
	 * @return boolean
	 */
	protected function _isInstance($modelInstance, $modelName)
	{
		return (is_subclass_of($modelInstance, $modelName) || (get_class($modelInstance) == $modelName));
	}
	
	/**
	 * Get page cache key
	 * @return array
	 */
	final public function getActionCacheData()
	{
		$res = $this->_getActionCacheData();
		$res['keys'][] = Mage::helper('msp_lts2')->getActionName();
        $res['keys'][] = Mage::helper('msp_lts2')->getUrl();
		$res['keys'][] = Mage::app()->getStore()->getId();
		return $res;
	}
	protected function _getActionCacheData()
	{
		return array('type' => self::HANDLING_NOCACHE, 'keys' => array(), 'lifetime' => 0, 'tags' => array());
	}

	/**
	 * Get block cache key
	 * @param string $blockName
	 * @return array
	 */
	final public function getBlockCacheData($blockName)
	{
		$res = $this->_getBlockCacheData($blockName);
		$res['keys'][] = $blockName;
		$res['keys'][] = Mage::app()->getStore()->getId();
		if (in_array($blockName, array('core_profiler', 'global_messages', 'messages', 'global_notices')))
		{
			$res['type'] = self::HANDLING_NOCACHE;
		}
		
		$res['tags'][] = 'MSP_BLOCK_NAME_'.$blockName;
		if (in_array($this->_getSessionCode(), $res['keys']))
			$res['tags'][] = 'MSP_BLOCK_SESSION_'.$blockName.'_'.$this->_getSessionCode();

		return $res;
	}
	protected function _getBlockCacheData($blockName)
	{
		return array('type' => self::HANDLING_NOCACHE, 'keys' => array(), 'lifetime' => 0, 'tags' => array());
	}

	/**
	 * Get invalidation block rules
	 * @param object $modelObject
	 * @return array
	 */
	final public function getBlocksModelInvalidation($modelObject)
	{
		$res = $this->_getBlocksModelInvalidation($modelObject);
		return $res;
	}
	protected function _getBlocksModelInvalidation($modelObject)
	{
		return array();
	}
}