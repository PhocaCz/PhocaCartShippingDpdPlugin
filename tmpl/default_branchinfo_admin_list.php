<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
// SHIPPING POINT NAME
$name = '';
if (isset($paramsShipping['name'])) {
    $name .= $paramsShipping['name'];
}

if (isset($paramsShipping['zip'])) {

    if ($name != '') {
        $name .= ', ';
    }

    $name .= $paramsShipping['zip'];
}

if (isset($paramsShipping['city'])) {

    if ($name != '') {
        $name .= ' ';
    }

    $name .= $paramsShipping['city'];
}

if (isset($paramsShipping['url'])) {
    $name = '<a href="'.$paramsShipping['url'].'" target="_blank">'.$name.'</a>';
}

echo '<div><b>'.Text::_('PLG_PCS_SHIPPING_DPD_PICK_UP_POINT'). ':</b> '. $name . '</div>';

// EXPORTED
if (isset($paramsShipping['exported']) && $paramsShipping['exported'] == 1) {
 $exported = '<div class="badge bg-success">'. Text::_('PLG_PCS_SHIPPING_DPD_YES') . '</div>';
} else {
 $exported = '<div class="badge bg-danger">'. Text::_('PLG_PCS_SHIPPING_DPD_NO') . '</div>';
}

echo '<div><b>'.Text::_('PLG_PCS_SHIPPING_DPD_EXPORTED'). ':</b> '. $exported . '</div>';


// COD TOTAL PAY
$totalPay = $item->total_amount;
$round = 0;
if ($item->currency_code == 'EUR') {
    $round = 2;
}
$totalPay = round($totalPay, $round);

$paymentZero = $paramsMethod->get('payment_zero', array());

$paymentMessage = [];
if (isset($item->payment_id) && in_array($item->payment_id, $paymentZero)) {
    $totalPay = 0;
    $paymentMessage[] = Text::_('COM_PHOCACART_PAYMENT_METHOD');
}

$statusZero = $paramsMethod->get('status_zero', array());
if (isset($item->status_id) && in_array($item->status_id, $statusZero)) {
    $totalPay = 0;
    $paymentMessage[] = Text::_('COM_PHOCACART_ORDER_STATUS');
}

if (isset($paramsShipping['totalPay']) && $paramsShipping['totalPay'] != '') {
    // User changed this parameter in orders view and stored it when exporting
    $totalPay = $paramsShipping['totalPay'];
    $paymentMessage[] = Text::_('PLG_PCS_SHIPPING_DPD_CUSTOM_CHANGE');
}

$paymentMessageOutput = '';
if (!empty($paymentMessage)) {
    $paymentMessageOutput = ' ('.Text::_('PLG_PCS_SHIPPING_DPD_PRICE_AFFECTED_BY') . ": ".implode(',', $paymentMessage).')';
}

echo '<div><b class="ph-order-info-box-label">'.Text::_('PLG_PCS_SHIPPING_DPD_TO_PAY'). ':</b> ';
echo '<input class="form-control" autocomplete="off" type="text" id="totalPay'.$item->id.'" name="totalPay['.$item->id.']" value="'.$totalPay.'" > ' . $item->currency_code . $paymentMessageOutput;
echo '</div>';

// TOTAL WEIGHT
$totalWeight    = '';
$defaultWeight  = $paramsMethod->get('default_weight', 0);
$unit           = '';
$maxWeight      = '';

if (isset($paramsShipping['total_weight'])) {

    if (isset($item->unit_weight) && $item->unit_weight != '') {
        $unit = ' ' . $item->unit_weight;
    }

    if (isset($paramsShipping['maxWeight'])) {
        $maxWeight = ' (' . Text::_('PLG_PCS_SHIPPING_DPD_MAXIMUM_WEIGHT') . ': ' . $paramsShipping['maxWeight'] . ' ' . Text::_('PLG_PCS_SHIPPING_DPD_KG') . ')';
    }

    $totalWeight = $paramsShipping['total_weight'];
    if (isset($defaultWeight) && $defaultWeight > 0 && ($paramsShipping['total_weight'] == 0 || $paramsShipping['total_weight'] == '')) {
        $totalWeight = $defaultWeight;// Default weight is in KG always
        $totalWeight = PhocaCartUtils::convertWeightFromKg($totalWeight, $item->unit_weight);
    }
}

if (isset($paramsShipping['totalWeight']) && $paramsShipping['totalWeight'] != '') {
    // User changed this parameter in orders view and stored it when exporting
    $totalWeight = $paramsShipping['totalWeight'];
}

echo '<div><b class="ph-order-info-box-label">'.Text::_('PLG_PCS_SHIPPING_DPD_WEIGHT'). ':</b> ';
echo '<input class="form-control" autocomplete="off" type="text" id="totalWeight'.$item->id.'" name="totalWeight['.$item->id.']" value="'.$totalWeight.'" >';
echo $unit . $maxWeight;
echo '</div>';


// ADULT CONTENT
/*
if (isset($paramsShipping['adultContent']) && $paramsShipping['adultContent'] != '') {
    // User changed this parameter in orders view and stored it when exporting
    $adultContent = $paramsShipping['adultContent'];
} else {
    $adultContent = $paramsMethod->get('adult_content', 0);
}

$checked = '';
$selectedYes = '';
$selectedNo = 'selected';
if ($adultContent == 1) {
    $checked = 'checked';
    $selectedYes = 'selected';
    $selectedNo = '';
}
*/
// Checkbox - not working in orders view, possible conflict with media/system/js/multiselect.js
// own multiselect in phocacart.js (see startTr in administrator/components/com_phocacart/views/phocacartorders/tmpl/default.php )
/*
echo '<div class="form-check">';
echo '<input class="form-check-input" autocomplete="off" type="checkbox" id="adultContent'.$item->id.'" name="adultContent[]" value="'.$item->id.'" '.$checked.' >';
echo ' <label class="form-check-label" for="adultContent'.$item->id.'">'.Text::_('PLG_PCS_SHIPPING_DPD_ADULT_CONTENT').'</label>';
echo '</div>';
*/
/*
echo '<select class="form-select" id="adultContent'.$item->id.'" name="adultContent['.$item->id.']">';
echo   '<option value="1" '.$selectedYes.'>Yes</option>';
echo   '<option value="0" '.$selectedNo.'>No</option>';
echo '</select>';
*/
/*
if (isset($paramsShipping['thumbnail']) && $paramsShippingShipping['thumbnail'] != '') {
    echo '<div class="ph-checkout-zasilkovna-info-photo"><img src="'.$paramsShippingShipping['thumbnail'].'" alt="'.$name.'" /></div>';

}
*/
