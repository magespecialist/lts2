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

class MSP_LTS2_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Check whenever we are using the admin area
     * @return boolean
     */
    public function isAdminArea()
    {
        if (Mage::app()->getStore()->isAdmin())
            return true;

        if (Mage::getSingleton('core/design_package')->getArea() != 'frontend')
            return true;

        return false;
    }

	/**
	 * Return session key
	 * @return string
	 */
	public function getSessionCode()
	{
		return session_id();
	}
	
	/**
	 * Return userid (0 for not loggedin)
	 * @return int
	 */
	public function getUserId()
	{
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			return 0;
	
		return Mage::getSingleton('customer/session')->getCustomer()->getId();
	}
	
	/**
	 * Return groupid (0 for not loggedin)
	 * @return int
	 */
	public function getGroupId()
	{
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			return 0;
	
		return Mage::getSingleton('customer/session')->getCustomer()->getGroupId();
	}
	
	/**
	 * Return full requested action name
	 * @return string
	 */
	public function getActionName()
	{
		$request = Mage::app()->getRequest();
		return implode('_', array(
			$request->getRequestedRouteName(),
			$request->getRequestedControllerName(),
			$request->getRequestedActionName(),
		));
	}

    /**
     * Return URL
     * @return string
     */
    public function getUrl()
    {
       return $_SERVER['REQUEST_URI'];
    }

	/**
	 * Return URL
	 * @return string
	 */
	public function getInternalUrl()
	{
		return Mage::app()->getRequest()->getRequestUri();
	}

	public function getStoreId() {
		return Mage::app()->getStore()->getId();
	}
}