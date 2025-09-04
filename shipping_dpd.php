<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');

if (file_exists(JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/bootstrap.php')) {
    // Joomla 5 and newer
    require_once(JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/bootstrap.php');
} else {
    // Joomla 4
    JLoader::registerPrefix('Phocacart', JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/phocacart');
}

class plgPCSShipping_Dpd extends CMSPlugin
{
    protected $name = 'shipping_dpd';

    function __construct(&$subject, $config) {
        parent:: __construct($subject, $config);
        $this->loadLanguage();
    }

    /* Export Shipping Branch Info */
    function onPCSexportShippingBranchInfo($context, $orderIds, $shippingInfo, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        $paramsMethod = $shippingInfo->params;
        $registry = new Registry;
        $registry->loadString($paramsMethod);
        $paramsMethod = $registry;

        /* Specific parameters set by users in orders view*/
        $additionalParameters = [];
        $additionalParameters['default_weight'] = Factory::getApplication()->input->get('default_weight', '');
        $additionalParameters['totalWeight']    = Factory::getApplication()->input->get('totalWeight', '');
        $additionalParameters['totalPay']       = Factory::getApplication()->input->get('totalPay', '');

        $o = '';
        if (!empty($orderIds)) {

            $db = Factory::getDBO();

            $wheres   = array();
            $wheres[] = 'o.id IN (' . implode(',', array_values($orderIds)) . ')';

            $query = ' SELECT DISTINCT o.*,'
                . ' os.title AS status_title,'
                . ' t.amount AS total_amount,'
                . ' s.id AS shippingid, s.title AS shippingtitle, s.tracking_link as shippingtrackinglink, s.tracking_description as shippingtrackingdescription, os.orders_view_display as ordersviewdisplay,'

                . ' us0.name_first as us0_name_first, us0.name_last as us0_name_last, us0.company as us0_company, us0.email as us0_email, us0.phone_1 as us0_phone_1, us0.phone_2 as us0_phone_2, us0.phone_mobile as us0_phone_mobile,'

                . ' us1.name_first as us1_name_first, us1.name_last as us1_name_last, us1.company as us1_company, us1.email as us1_email, us1.phone_1 as us1_phone_1, us1.phone_2 as us1_phone_2, us1.phone_mobile as us1_phone_mobile'

                . ' FROM #__phocacart_orders AS o'
                . ' LEFT JOIN #__phocacart_order_statuses AS os ON os.id = o.status_id'
                . ' LEFT JOIN #__phocacart_order_total AS t ON o.id = t.order_id AND t.type = \'brutto\''
                . ' LEFT JOIN #__phocacart_shipping_methods AS s ON s.id = o.shipping_id'
                . ' LEFT JOIN #__phocacart_order_users AS us0 ON o.id=us0.order_id AND us0.type = 0'
                . ' LEFT JOIN #__phocacart_order_users AS us1 ON o.id=us1.order_id AND us1.type = 1'
                . ' WHERE ' . implode(' AND ', $wheres)
                . ' GROUP BY o.id'
                . ' ORDER BY o.id';

            $db->setQuery($query);
            $orders = $db->loadObjectList();

            $path = PluginHelper::getLayoutPath('pcs', 'shipping_dpd', 'default_branchinfo_export');

            // Render the output
            ob_start();
            include $path;
            $o = ob_get_clean();
        }

        return $o;
    }

    /* Order edit view - administration */
    function onPCSgetShippingBranchInfoAdminEdit($pid, $item, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        $output = array();
        return $output;
    }

    /* Order list view - administration */
    function onPCSgetShippingBranchInfoAdminList($pid, $item, $shippingInfo, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        $paramsShipping = json_decode($item->params_shipping, true);
        $paramsMethod   = $shippingInfo->params;

        $registry = new Registry;
        $registry->loadString($paramsMethod);
        $paramsMethod = $registry;

        // Get the path for the layout file
        $path = PluginHelper::getLayoutPath('pcs', 'shipping_dpd', 'default_branchinfo_admin_list');

        // Render the output
        ob_start();
        include $path;
        $o = ob_get_clean();

        $output            = array();
        $output['content'] = $o;

        return $output;
    }

    /* Render button for selecting DPD pickup points */
    function onPCSgetShippingBranches($context, &$item, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        $document = Factory::getDocument();
        $app      = Factory::getApplication();

        $registry = new Registry;
        $registry->loadString($item->params);
        $item->params = $registry;

        $pC = PhocacartUtils::getComponentParameters();

        $id      = (int)$item->id;
        $oParams = array();
        $oParams[$id]['fields']                = $this->getBranchFields();
        $oParams[$id]['validate_pickup_point'] = $item->params->get('validate_pickup_point', 1);
        $oParams[$id]['display_opening_hours'] = $item->params->get('display_opening_hours', 0);
        $oParams[$id]['display_branch_photo']  = $item->params->get('display_branch_photo', 0);
        $oParams[$id]['api_key']               = $item->params->get('api_key', '');
        $oParams[$id]['api_secret']            = $item->params->get('api_secret', '');
        $oParams[$id]['theme']                 = $pC->get('theme', 'bs5');

        $oLang = array(
            'MONDAY' => Text::_('MONDAY'),
            'TUESDAY' => Text::_('TUESDAY'),
            'WEDNESDAY' => Text::_('WEDNESDAY'),
            'THURSDAY' => Text::_('THURSDAY'),
            'FRIDAY' => Text::_('FRIDAY'),
            'SATURDAY' => Text::_('SATURDAY'),
            'SUNDAY' => Text::_('SUNDAY'),
            'PLG_PCS_SHIPPING_DPD_NONE' => Text::_('PLG_PCS_SHIPPING_DPD_NONE'),
            'PLG_PCS_SHIPPING_DPD_ERROR_PLEASE_SELECT_PICK_UP_POINT' => Text::_('PLG_PCS_SHIPPING_DPD_ERROR_PLEASE_SELECT_PICK_UP_POINT'),
            'PLG_PCS_SHIPPING_DPD_SEARCH_PLACEHOLDER' => Text::_('PLG_PCS_SHIPPING_DPD_SEARCH_PLACEHOLDER'),
            'PLG_PCS_SHIPPING_DPD_SELECT_PICKUP_POINT' => Text::_('PLG_PCS_SHIPPING_DPD_SELECT_PICKUP_POINT'),
            'PLG_PCS_SHIPPING_DPD_SELECTED_POINT' => Text::_('PLG_PCS_SHIPPING_DPD_SELECTED_POINT'),
            'PLG_PCS_SHIPPING_DPD_CHANGE_POINT' => Text::_('PLG_PCS_SHIPPING_DPD_CHANGE_POINT')
        );

        $document->addScriptOptions('phParamsPlgPcsDpd', $oParams);
        $document->addScriptOptions('phLangPlgPcsDpd', $oLang);

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->useScript('core')->registerAndUseScript('plg_pcs_shipping_dpd.dpd-int', 'media/plg_pcs_shipping_dpd/js/dpd.js', array('version' => 'auto'));
        $wa->registerAndUseStyle('plg_pcs_shipping_dpd.dpd-css', 'media/plg_pcs_shipping_dpd/css/dpd.css', array('version' => 'auto'));

        // Get the path for the layout file
        $path = PluginHelper::getLayoutPath('pcs', 'shipping_dpd', 'default_selectpoint');

        // Render the output
        ob_start();
        include $path;
        $o = ob_get_clean();

        $output            = array();
        $output['content'] = $o;

        return $output;
    }

    /* Check all input branch form fields to protect database from saving wrong values */
    function onPCScheckShippingBranchFormFields($context, &$items, $shippingMethod, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        // Allowed fields + filter values
        $fields = $this->getBranchFields();

        if (!empty($items)) {
            foreach ($items as $k => $v) {

                // Remove not allowed fields
                if (!in_array($k, $fields)) {
                    unset($items[$k]);
                } else {
                    if ($k == 'opening_hours') {
                        $items[$k] = PhocacartText::filterValue($v, 'text-div');
                    } else {
                        $items[$k] = PhocacartText::filterValue($v, 'text');
                    }
                }
            }
        }

        return true;
    }

    function onPCSgetShippingBranchInfo($context, $shippingMethod, $params, $eventData) {

        if (!isset($eventData['pluginname']) || isset($eventData['pluginname']) && $eventData['pluginname'] != $this->name) {
            return false;
        }

        $document = Factory::getDocument();
        $app      = Factory::getApplication();
        $wa       = $app->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('plg_pcs_shipping_dpd.dpd-css', 'media/plg_pcs_shipping_dpd/css/dpd.css', array('version' => 'auto'));

        $paramsMethod                          = [];
        $paramsMethod['display_opening_hours'] = $shippingMethod['params']->get('display_opening_hours', 0);

        // Get the path for the layout file
        $path = PluginHelper::getLayoutPath('pcs', 'shipping_dpd', 'default_branchinfo');

        // Render the output
        ob_start();
        include $path;
        $o = ob_get_clean();

        $output            = array();
        $output['content'] = $o;

        return $output;
    }

    /*
     * Which Branch info will be stored to order for DPD pickup points
     */
    function getBranchFields() {
        return array(
            'id',
            'depot',
            'name',
            'street',
            'zip',
            'city',
            'country',
            'phone',
            'email',
            'opening_hours',
            'latitude',
            'longitude',
            'type'
        );
    }

    /**
     * Get DPD API credentials
     */
    private function getApiCredentials($shippingMethod) {
        $registry = new Registry;
        $registry->loadString($shippingMethod->params);
        $params = $registry;

        return [
            'username' => $params->get('api_username', ''),
            'password' => $params->get('api_password', ''),
            'depot' => $params->get('depot_number', ''),
            'test_mode' => $params->get('test_mode', 1)
        ];
    }

    /**
     * Call DPD API to get pickup points
     */
    private function getDpdPickupPoints($postcode, $city = '', $maxResults = 10) {
        // This would integrate with DPD's API
        // For now returning sample structure
        return [
            [
                'parcel_shop_id' => 'CZ12345',
                'name' => 'DPD Pickup Point',
                'company' => 'X s.r.o.',
                'street' => 'Václavské náměstí',
                'street_number' => '1',
                'zip_code' => '11000',
                'city' => 'Praha',
                'country' => 'CZ',
                'phone' => '+420xxxyyyzzz',
                'latitude' => '50.0832',
                'longitude' => '14.4281',
                'opening_hours' => 'Po-Pá: 8:00-18:00, So: 9:00-12:00',
                'max_shipment_weight' => '31.5',
                'services' => ['pickup', 'return']
            ]
        ];
    }
}
?>
