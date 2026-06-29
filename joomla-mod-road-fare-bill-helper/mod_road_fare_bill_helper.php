<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_road_fare_bill_helper
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

require ModuleHelper::getLayoutPath('mod_road_fare_bill_helper', $params->get('layout', 'default'));
