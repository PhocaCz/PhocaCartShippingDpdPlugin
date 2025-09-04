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
echo '<div class="ph-cb"></div>';
echo '<div class="ph-checkout-shipping-info-box ph-checkout-dpd-info-box">';

$name = '';
if (isset($params['name'])) {
    $name .= $params['name'];
}

if (isset($params['zip'])) {

    if ($name != '') {
        $name .= ', ';
    }

    $name .= $params['zip'];
}

if (isset($params['city'])) {

    if ($name != '') {
        $name .= ' ';
    }

    $name .= $params['city'];
}


if (isset($params['thumbnail']) && $params['thumbnail'] != '') {
    echo '<div class="ph-checkout-dpd-info-photo"><img src="'.$params['thumbnail'].'" alt="'.$name.'" /></div>';

}
if ($name != '') {
    echo '<div class="ph-checkout-dpd-info-name">'.$name.'</div>';
}

if ($paramsMethod['display_opening_hours'] == 1 && $params['opening_hours'] != '') {
    echo '<div class="ph-checkout-dpd-info-opening-hours">'.$params['opening_hours'].'</div>';
}

echo '</div>';
