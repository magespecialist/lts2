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

class MSP_LTS2_Model_Layout
{
	const LAYOUT_CLASS = 'Mage_Core_Model_Layout_Element';
	protected $_layout = null;

    protected $_blocksName = array();
    protected $_layoutHandles = array();
	
	public function getLayout()
	{
		return $this->_layout;
	}

    /**
     * Set layout information for fancy loading
     * @param array $blocksName
     * @param array $layoutHandles
     */
    public function setLayoutInfo(array $blocksName, array $layoutHandles)
    {
        $this->_blocksName = $blocksName;
        $this->_layoutHandles = $layoutHandles;
    }
	
	/**
	 * Generate a layout only containing dynamic blocks
	 * @return MSP_LTS2_Model_Layout
	 */
	public function loadLayoutObsolete()
	{
		if (!is_null($this->_layout)) return $this;

        $blocksName = $this->_blocksName;
        $layoutHandles = $this->_layoutHandles;

		$layout = Mage::getSingleton('core/layout');
		$package = Mage::getSingleton('core/design_package');
	
		if (!in_array('messages', $this->_blocksName)) $blocksName[] = 'messages';
		if (!in_array('global_messages', $this->_blocksName)) $blocksName[] = 'global_messages';
	
		$cache = Mage::getSingleton('msp_lts2/cache');
		
		$cacheKeyParts = array_merge($this->_layoutHandles, $blocksName);
		$cacheKey = 'msp_lts_layout_'.md5(implode('::', $cacheKeyParts));
		
		Varien_Profiler::start(__METHOD__);
		if ($dynamicLayout = $cache->getCacheItem($cacheKey))
		{
            $cache->setCacheLayoutStatus(MSP_LTS2_Model_Cache::CACHE_STATUS_HIT);
			$dynamicLayout = simplexml_load_string($dynamicLayout, static::LAYOUT_CLASS);
		}
		else
		{
			$update = $layout->getUpdate();
			
			foreach ($layoutHandles as $handle)
				$update->addHandle($handle);
			
			$update->load();
	
			$layout->generateXml();
	
			$layoutXml = $layout->getXmlString();
				
			$xml = simplexml_load_string($layoutXml, self::LAYOUT_CLASS);
	
			// Create empty new layout
			$dynamicLayout = simplexml_load_string('<layout/>', self::LAYOUT_CLASS);
	
			$types = array('block', 'reference', 'action');
			foreach ($blocksName as $blockName)
			{
				foreach ($types as $type)
				{
					$xPath = $xml->xpath("//" . $type . "[@name='" . $blockName . "']");
					foreach ($xPath as $child)
					{
						$dynamicLayout->appendChild($child);
					}
				}
			}
	
			$cache->setCacheItem($cacheKey, $dynamicLayout->asNiceXml());
		}
	
		// Switch layouts
		$layout->setXml($dynamicLayout);
		$layout->generateBlocks();
	
		// Add messages
		$storageNames = array(
			'catalog/session',
			'catalogsearch/session',
			'checkout/session',
			'customer/session',
			'core/session',
			'paypal/session',
			'review/session',
			'tag/session',
			'wishlist/session',
		);
	
		foreach ($storageNames as $storageName)
		{
			$storage = Mage::getSingleton($storageName);
			$messageBlock = $layout->getMessagesBlock();
			$messageBlock->addMessages($storage->getMessages(true));
			$messageBlock->setEscapeMessageFlag($storage->getEscapeMessages(true));
			$messageBlock->addStorageType($storageName);
		}
	
		Varien_Profiler::stop(__METHOD__);
		
		$this->_layout = $layout;
        return $this;
	}

    /**
     * Generate a layout only containing dynamic blocks
     * @return MSP_LTS2_Model_Layout
     */
    public function loadLayout()
    {
        if (!is_null($this->_layout)) return $this;

        $blocksName = $this->_blocksName;
        $layoutHandles = $this->_layoutHandles;
        $layoutHandles[] = Mage::getSingleton('customer/session')->isLoggedIn() ? 'customer_logged_in' : 'customer_logged_out';

        if (!in_array('messages', $this->_blocksName)) $blocksName[] = 'messages';
        if (!in_array('global_messages', $this->_blocksName)) $blocksName[] = 'global_messages';

        $cache = Mage::getSingleton('msp_lts2/cache');

        $cacheKeyParts = array_merge($layoutHandles, $blocksName);
        $cacheKey = 'msp_lts_layout_'.md5(implode('::', $cacheKeyParts));

        $layout = Mage::getSingleton('core/layout');

        Varien_Profiler::start(__METHOD__);
        if ($dynamicLayout = $cache->getCacheItem($cacheKey))
        {
            $cache->setCacheLayoutStatus(MSP_LTS2_Model_Cache::CACHE_STATUS_HIT);
            $dynamicLayout = simplexml_load_string($dynamicLayout, static::LAYOUT_CLASS);
        }
        else
        {
            $update = $layout->getUpdate();

            foreach ($layoutHandles as $handle)
                $update->addHandle($handle);

            $update->load();

            $layout->generateXml();

            $layoutXml = $layout->getXmlString();

            $xml = simplexml_load_string($layoutXml, self::LAYOUT_CLASS);

            // Create empty new layout
            $dynamicLayout = simplexml_load_string('<layout/>', self::LAYOUT_CLASS);

            foreach ($blocksName as $blockName)
            {
                $xPath = $xml->xpath("//block[@name='".$blockName."']");
                foreach ($xPath as $child)
                {
                    $dynamicLayout->appendChild($child);
                }
            }

            // Adding references
						$parsedBlocks = array();
            $xPath = $dynamicLayout->xpath("//block");
            foreach ($xPath as $child)
            {
                $attrs = $child->attributes();
                if (!isset($attrs['name'])) continue;
                $blockName = $attrs['name']->__toString();

								if(in_array($blockName, $parsedBlocks)) continue;

                $xPath2 = $xml->xpath("//reference[@name='".$blockName."']");
                foreach ($xPath2 as $child2)
                {
                    $dynamicLayout->appendChild($child2);
                }

								$parsedBlocks[] = $blockName;
            }

            $cache->setCacheItem($cacheKey, $dynamicLayout->asNiceXml());
        }

        // Switch layouts
        $layout->setXml($dynamicLayout);
        $layout->generateBlocks();

        // Add messages
        $storageNames = array(
            'catalog/session',
            'catalogsearch/session',
            'checkout/session',
            'customer/session',
            'core/session',
            'paypal/session',
            'review/session',
            'tag/session',
            'wishlist/session',
        );

        foreach ($storageNames as $storageName)
        {
            $storage = Mage::getSingleton($storageName);
            $messageBlock = $layout->getMessagesBlock();
            $messageBlock->addMessages($storage->getMessages(true));
            $messageBlock->setEscapeMessageFlag($storage->getEscapeMessages(true));
            $messageBlock->addStorageType($storageName);
        }

        Varien_Profiler::stop(__METHOD__);

        $this->_layout = $layout;
        return $this;

    }
}
