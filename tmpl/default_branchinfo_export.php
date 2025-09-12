<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
$eA = array();
$price = new PhocacartPrice();

// When exported, we need to save this info
$ordersSave = [];

if (!empty($orders)) {

    //$apiKey         = $paramsMethod->get('api_key', '');
    $senderName     = $paramsMethod->get('sender_name', '');
    $defaultWeight  = $paramsMethod->get('default_weight', '');
    $saveChanges    = $paramsMethod->get('save_changes', 1);
    foreach($orders as $k => $v){


        // If users wants to store parameters he/she changed in orders view
        $changeTotalPay     = '';
        $changeTotalWeight       = '';
        $changeAdultContent = '';


        $paramsShipping = json_decode($v->params_shipping);

        $id = (int)$v->id;


        // Name
        $nameFirst = $v->us1_name_first != '' ? $v->us1_name_first : $v->us0_name_first;
        $nameLast = $v->us1_name_last != '' ? $v->us1_name_last : $v->us0_name_last;
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_NAME') => $nameFirst . ' '.$nameLast];

        // Company
        $company    = $v->us1_company != '' ? $v->us1_company : $v->us0_company;
        if ($company != '') {
            $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_COMPANY') => $company];
        }

        // Address
        $address = $v->us1_address_1 != '' ? $v->us1_address_1 : $v->us0_address_1;
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ADDRESS') => $address];

        // City
        $city = $v->us1_city != '' ? $v->us1_city : $v->us0_city;
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_CITY') => $city];

        // ZIP
        $zip = $v->us1_zip != '' ? $v->us1_zip : $v->us1_zip;
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ZIP') => $zip];

        // Country
        $countryId = $v->us1_country != '' ? $v->us1_country : $v->us1_country;

        $db = Factory::getDBO();
		$query = 'SELECT a.code2'
		. ' FROM #__phocacart_countries AS a'
		. ' WHERE a.id = '.(int)$countryId
        . ' LIMIT 1';
		$db->setQuery( $query );
		$countryCode = $db->loadResult();
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_COUNTRY') => $countryCode];

        // Phone
        if ($v->us1_phone_1 != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us1_phone_1];
        } else if ($v->us0_phone_1 != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us0_phone_1];
        } else if ($v->us1_phone_2 != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us1_phone_2];
        } else if ($v->us0_phone_2 != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us0_phone_2];
        } else if ($v->us1_phone_mobile != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us1_phone_mobile];
        } else if ($v->us0_phone_mobile != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_PHONE') => $v->us0_phone_mobile];
        } else {
            //$eA[$id][] = ['phoneNumber' => ''];// no empty value
        }

        // Email
        $eA[$id][] = $v->us1_email != '' ? [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_EMAIL') => $v->us1_email] : [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_EMAIL') => $v->us0_email];

        // Order Number
        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ORDER_NUMBER') => $v->order_number];
        //$eA[$id][]   = ['senderLabel' => $senderName];

        $currency    = $v->currency_code;
        if ($currency != '') {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ORDER_CURRENCY') => $currency];
        }
        // COD
        $round = 0;
        if ($v->currency_code == 'EUR') {
            $round = 2;
        }
        // Here additional currencies can have own rules

        if (isset($additionalParameters['totalPay'][$id])) {
            // To Pay set by the form field in orders view
            $changeTotalPay = round($additionalParameters['totalPay'][$id], $round);
            $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_TO_PAY') => $changeTotalPay];

        } else {
            $changeTotalPay = round($v->total_amount, $round);
            $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_TO_PAY') => $changeTotalPay];
        }



        $eA[$id][]   = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ORDER_VALUE') => round($v->total_amount, $round)];


        $unitWeight = $v->unit_weight;
        $weightKg = $defaultWeight;// this is weight in KG
        $weightInStoredUnit = $defaultWeight;

        if (isset($additionalParameters['totalWeight'][$id]) && (int)$additionalParameters['totalWeight'][$id] > 0) {
            // Weight set by the form field in orders view
            $weightKg =  $weightInStoredUnit = $additionalParameters['totalWeight'][$id];
            $weightKg = PhocacartUtils::convertWeightToKg($weightKg, $unitWeight);
        } if (isset($paramsShipping->total_weight) && $paramsShipping->total_weight > 0) {
            // If not, try to find it in total weight
            $weightKg = $weightInStoredUnit = $paramsShipping->total_weight;
            $weightKg = PhocacartUtils::convertWeightToKg($weightKg, $unitWeight);
        }

        if ($weightKg != '' && $weightKg != 0) {
            $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_WEIGHT') => $weightKg];
        }

        if ($weightKg != '') {
            //$changeTotalWeight = $weightKg;
            $changeTotalWeight = $weightInStoredUnit;
        }

        // Destination
        // Not clever but ceskaposta returns street with number but it demands street without number
        // so we try to separate it
        /*$street = '';
        $houseNumber = '';
        if ($paramsShipping->street != '') {
            $match = '';
            preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $paramsShipping->street, $match);
            if (isset($match[1])) {
                $street = $match[1];
            }
            if (isset($match[2])){
                $houseNumber = $match[2];
            }
        }*/

       // $eA[$id][]   = ['type' => $paramsShipping->type];
      //  $eA[$id][]   = ['subtype' => $paramsShipping->subtype];

        $eA[$id][] = [Text::_('PLG_PCS_SHIPPING_DPD_CSV_HEADER_ID_PICKUP_POINT') => $paramsShipping->id];


       /* $eA[$id][]['destination'] = [
            'pickupPointOrCarrier' => $paramsShipping->id,
            /*'street' => $street,
            'houseNumber' => $houseNumber,
            'city' => $paramsShipping->city,
            'zip' => $paramsShipping->zip,*//*
            'name' => $paramsShipping->name,
            'municipality_district_name' => $paramsShipping->municipality_district_name,
            'municipality_name' => $paramsShipping->municipality_name,
            'address' => $paramsShipping->address,
            'country' => $paramsShipping->country
        ];*/


        // Selectbox
       /* if (isset($additionalParameters['adultContent'][$id]) && (int)$additionalParameters['adultContent'][$id] == 1) {
            $eA[$id][] = ['adultContent' => 1];
            $changeAdultContent = 1;
        }*/

        // Checkbox - not working in orders view, possible conflict with media/system/js/multiselect.js
   /*     if (in_array($id, $additionalParameters['adultContent'])) {
            $eA[$id][] = ['adultContent' => 1];
            $changeAdultContent = 1;
        } else {
            //$eA[$id][] = ['adultContent' => 0];// NOT REQUIRED
        }*/


        // Change the info, set "exported to 1
        $paramsShipping->exported = 1;
        if ($saveChanges == 1) {
            $paramsShipping->totalPay     = $changeTotalPay;
            $paramsShipping->totalWeight  = $changeTotalWeight;
            //$paramsShipping->adultContent = $changeAdultContent;
        }

        $ordersSave[$id] = json_encode($paramsShipping);

    }


    $oCsv = [];
    $oCsvHeader = [];

    if (!empty($eA)) {
        $i = 0;
        foreach ($eA as $k => $v) {
            if (!empty($v)){
                foreach ($v as $k2 => $v2) {
                    $key = key($v2);

                    if ($i == 0) {
                        $oCsvHeader[] = '"' . $key . '"';
                    }
                    $oCsv[$k][] = '"'.$v2[$key].'"';
                }
            }
            $i++;
        }
    }

    /*
    if (!empty($eA)) {
        $oXml[] = '<parcels version="1">';
        foreach ($eA as $k => $v) {

            if (!empty($v)){
                $oXml[] = $t .'<parcel>';
                foreach ($v as $k2 => $v2) {


                    if (isset($v2['destination'])) {

                        if (!empty($v2)){
                            $oXml[] = $t . $t . '<destination>';


                            foreach ($v2['destination'] as $k3 => $v3) {


                                $oXml[] = $t.$t.$t .'<'.$k3.'>' .$v3. '</'.$k3.'>';
                            }
                            $oXml[] = $t . $t . '</destination>';
                        }

                    } else {
                        $key = key($v2);
                        $oXml[] = $t.$t. '<'.$key.'>' .$v2[$key]. '</'.$key.'>';
                    }
                }
                $oXml[] = $t . '</parcel>';
            }


        }

        $oXml[] = '</parcels>';
    }
    */


    if (!empty($oCsvHeader)){

        $fileCsv = implode(",", $oCsvHeader);

        if (!empty($oCsv)) {
            foreach ($oCsv as $k => $v) {
                $fileCsv .= "\n" . implode(",", $v);
            }

            $date = date("Y-m-d-H-i-s");


            // Update the info about exported
            if (!empty($ordersSave)) {
                $db = Factory::getDBO();
                foreach ($ordersSave as $k => $v) {

                    if ((int)$k > 0 && $v != '') {

                        $query = 'UPDATE #__phocacart_orders SET'
                            . ' params_shipping = ' . $db->quote($v) . ''
                            . ' WHERE id = ' . (int)$k;
                        $db->setQuery($query);
                        $db->execute();
                    }

                }
            }

            PhocacartDownload::downloadContent($fileCsv, '', '', $date . '-' . Text::_('PLG_PCS_SHIPPING_DPD_EXPORT_FILENAME') . '.csv', 'text/csv');
            exit;
        }
    }
}


