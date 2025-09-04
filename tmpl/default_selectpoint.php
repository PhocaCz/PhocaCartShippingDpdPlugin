<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;
// When selecting point, make the checkbox active
$id = (int)$item->id;
$idCheckbox = 'phshippingopt'.$id;
$s          = PhocacartRenderStyle::getStyles();
$t          = [];
$layoutAAQ	= new FileLayout('popup_container_iframe', null, array('component' => 'com_phocacart'));
$type       = $item->params->get('type', 'BALIKOVNY');


$attributeToggle = '';
if ($s['c']['class-type'] == 'uikit') {
    $attributeToggle = ' uk-toggle="target: #phPcsDpdPopup'.$id.'"';
}

// DEMO: https://api.dpd.cz/widget/latest/demo.html
// https://api.dpd.cz/widget/latest/index.html
$widgetUrl = 'https://api.dpd.cz/widget/latest/index.html?hideCloseButton=true';
?>
<div class="ph-checkout-shipping-additional-info ph-checkout-dpd-box">
    <a href="<?php echo $widgetUrl ?>"<?php echo $attributeToggle ?> data-bs-target="#phPcsDpdPopup<?php echo $id ?>" data-bs-toggle="modal" data-id="phPcsDpdPopup<?php echo $id ?>" data-src="<?php echo $widgetUrl ?>" class="ph-btn btn ph-btn-dpd-select-pickup-point phModalContainerCommonIframeButton" role="button" title="<?php echo Text::_('PLG_PCS_SHIPPING_DPD_SELECT_PICK_UP_POINT'); ?>"  data-bs-toggle="tooltip" data-placement="top" onclick="phSetCheckboxActive('<?php echo $idCheckbox ?>')"><?php echo Text::_('PLG_PCS_SHIPPING_DPD_SELECT_PICK_UP_POINT'); ?></a>
    <input type="hidden" id="dpd-checkbox-id-<?php echo $item->id ?>" value="<?php echo $idCheckbox ?>" >
    <div class="ph-checkout-dpd-info-container"><div class="ph-checkout-dpd-info-label"><?php echo Text::_('PLG_PCS_SHIPPING_DPD_SELECTED_POINT'); ?>:</div> <div class="ph-checkout-shipping-info-box ph-checkout-dpd-info-box" id="dpd-point-info-<?php echo $item->id ?>"><?php echo Text::_('PLG_PCS_SHIPPING_DPD_NONE'); ?></div><?php
    if (!empty($oParams[$id]['fields'])) {
        foreach($oParams[$id]['fields'] as $k => $v) {
            echo '<input type="hidden" id="dpd-field-'.$v.'-'.$item->id.'" name="phshippingmethodfield['.$item->id.']['.$v.']" value="" />';
        }
    }
    ?></div>
</div><?php

PhocacartRenderJs::renderModalCommonIframeWindow(['allow_geolocation' => true]);
//echo '<div id="phContainer"></div>';
echo '<div id="phContainerPopupPcs'.$id .'" class="phContainerPopup">';
$d						= array();
$d['id']				= 'phPcsDpdPopup'.$id;
$d['title']				= Text::_('PLG_PCS_SHIPPING_DPD_SELECT_PICK_UP_POINT');
$d['icon']				= $s['i']['marker'];
$d['closebutton']       = 1;
$d['modal-class'] = 'modal-xl';
// Solved in Phoca Cart YOOtheme CSS
/*if ($s['c']['class-type'] == 'uikit') {
    $d['modal-custom-style'] = 'height: 80vh';
}*/
$d['t']					= $t;
$d['s']					= $s;
echo $layoutAAQ->render($d);
echo '</div>';// end phContainerPopup
?>

