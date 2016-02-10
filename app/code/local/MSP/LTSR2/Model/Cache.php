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
 * @package    MSP_LTSR2
 * @copyright  Copyright (c) 2013 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license    http://www.idealiagroup.com/magento-ext-license.html
 */

class MSP_LTSR2_Model_Cache extends MSP_LTS2_Model_Rule_Cache
{
    public function isCacheDebugActive()
    {
        return false;
    }

    protected function _getBlockCacheData($blockName)
    {
        $type = self::HANDLING_STATIC;
        $keys = array();
        $lifetime = 86400*30;
        $tags = array();

        $cache = Mage::getSingleton('msp_lts2/cache');
        if ($cache->canCacheAction())
        {
            if (in_array($blockName, array(
                'breadcrumbs',
            ))) {
                $keys[] = Mage::helper('msp_lts2')->getUrl();
                $type = self::HANDLING_CACHE;
            }

            if (in_array($blockName, array(
                'head',
                'content',
            ))) {
                $keys[] = Mage::helper('msp_lts2')->getUrl();
                $type = self::HANDLING_STATIC;
            }

            $actionName = Mage::helper('msp_lts2')->getActionName();

            if (in_array($actionName, array(
                'catalog_category_view',
                'catalog_category_default',
                'catalog_category_layered',
            ))) {
                if (in_array($blockName, array(
                    'content',
                ))) {
                    $lifetime = 3600;
                    $session = Mage::getSingleton('catalog/session');
                    $keys[] = Mage::helper('msp_lts2')->getUrl();
                    $keys[] = $session->getData('sort_order');
                    $keys[] = $session->getData('sort_direction');
                    $keys[] = $session->getData('display_mode');

                }
            }
        }

        if (in_array($blockName, array(
            'customer_form_mini_login',
        ))) {
            $type = self::HANDLING_CACHE;
            $keys[] = $this->_getUserId();
        }

        if (in_array($blockName, array(
            'formkey',
            'newsletter.subscribe',
        ))) {
            $type = self::HANDLING_CACHE;
            $keys[] = $this->_getSessionCode();
        }

        // Session related
        if (in_array($blockName, array(
            'cart_sidebar',
            'cart_sidebar_mini',
            'top.links',
            'cart_top',
            'top.container',
            'wishlist_link',
            'checkout_cart_link',
        ))) {
            $keys[] = $this->_getSessionCode();
            $keys[] = $this->_getUserId();
            $lifetime = 60*15;
            $type = self::HANDLING_CACHE;
        }

        return array('type' => $type, 'keys' => $keys, 'lifetime' => $lifetime, 'tags' => $tags);
    }

    protected function _getActionCacheData()
    {
        $type = self::HANDLING_NOCACHE;
        $lifetime = 86400*30;
        $tags = array();
        $keys = array();

        $actionName = Mage::helper('msp_lts2')->getActionName();

        if (in_array($actionName, array(
            'cms_index_index',
            'cms_page_view',
            'catalog_product_view',
            'catalog_category_default',
            'catalog_category_view',
            'catalog_category_layered',
        ))) {
            $type = self::HANDLING_CACHE;

            $keys[] = serialize($_GET);
        }

        return array('type' => $type, 'lifetime' => $lifetime, 'tags' => $tags, 'keys' => $keys);
    }

    protected function _getBlocksModelInvalidation($modelInstance)
    {
        $return = array('global' => array(), 'session' => array());

        if ($this->_isInstance($modelInstance, 'Mage_Sales_Model_Quote'))
        {
            // Blocks here must be defined as "self::HANDLING_CACHE"
            $return['session'][] = 'cart_sidebar';
            $return['session'][] = 'cart_sidebar_mini';
            $return['session'][] = 'top.container';
            $return['session'][] = 'top.links';
            $return['session'][] = 'cart_top';
            $return['session'][] = 'checkout_cart_link';
            $return['session'][] = 'wishlist_link';
        }

        return $return;
    }
}