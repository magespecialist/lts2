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

class MSP_LTS2_Model_Observer
{
    protected function _getDebug()
    {
        if (!Mage::getSingleton('msp_ltsr2/cache')->isCacheDebugActive())
            return '';

        ob_start();
        include(BP.DS.'app'.DS.'code'.DS.'local'.DS.'MSP'.DS.'LTS2'.DS.'libs'.DS.'debug.phtml');
        $debug = ob_get_clean();
        ob_end_clean();

        return $debug;
    }

    protected function _setLayoutInformation()
    {
        /* @var $cache MSP_LTS2_Model_Cache */
        $cache = Mage::getSingleton('msp_lts2/cache');

        $blocks = $cache->getActionBlocks();
        $handles = $cache->getActionLayoutHandles();

        $invalidBlocks = array();
        foreach ($blocks as $blockName)
        {
            if (!$cache->cacheItemExists($cache->getBlockCacheKey($blockName)))
                $invalidBlocks[] = $blockName;
        }

        Mage::getSingleton('msp_lts2/layout')->setLayoutInfo($invalidBlocks, $handles);
    }
	
	public function onControllerActionLayoutLoadBefore(Varien_Event_Observer $observer)
	{
		if (Mage::helper('msp_lts2')->isAdminArea())
			return $this;

        //Mage::register('msp_lts_start2', microtime(true));

		$cacheIsActive = Mage::helper('msp_lts2/cache')->isActive();
		if (!$cacheIsActive)
			return $this;

        Mage::app()->setUseSessionVar(false);
        Mage::app()->setUseSessionInUrl(false);
	
		/* @var $cache MSP_LTS2_Model_Cache */
		$cache = Mage::getSingleton('msp_lts2/cache');
	
		if (!$cache->canCacheAction())
			return $this;

		if ($body = $cache->getAction())
		{
            $this->_setLayoutInformation();
            $cache->setCacheActionStatus(MSP_LTS2_Model_Cache::CACHE_STATUS_HIT);

			Mage::register('msp_lts_mode_action', true);

			$body = $cache->replacePlaceholders($body);
			$body = $cache->replaceFormKeys($body);

			Mage::app()->getResponse()->setBody($body.$this->_getDebug());
			Mage::app()->getResponse()->sendResponse();
			exit;
		}
	}
	
	public function onHttpResponseSendBefore(Varien_Event_Observer $observer)
	{
        if (Mage::helper('msp_lts2')->isAdminArea())
			return $this;
	
		if (Mage::registry('msp_lts_mode_action'))
			return $this;
		
		/* @var $cache MSP_LTS2_Model_Cache */
		$cache = Mage::getSingleton('msp_lts2/cache');
	
		$body = $observer->getEvent()->getResponse()->getBody();
		$cacheIsActive = Mage::helper('msp_lts2/cache')->isActive();
		if ($cacheIsActive && $cache->canCacheAction())
		{
			$cacheBody = $body;

            $this->_setLayoutInformation();

			$body = $cache->replacePlaceholders($body);
			$observer->getEvent()->getResponse()->setBody($body.$this->_getDebug());
			
			// Must run here to get the right list of involved blocks
			$cache->setAction($cacheBody, $cache->getInvolvedBlocksList(), Mage::getSingleton('core/layout')->getUpdate()->getHandles());
		}
	}

	public function onModelSaveAfter(Varien_Event_Observer $observer)
	{
		if (!Mage::helper('msp_lts2/cache')->isActive())
			return $this;
	
		Mage::getSingleton('msp_lts2/cache')->invalidateOnModelSave($observer->getEvent()->getObject());
	}
}
