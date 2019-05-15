<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

/*
 * Some characters you may want to copy&paste:
 * ’ » “ ” …
 */
$lang = array_merge($lang, array(
	'ACL_A_WRW_ADMIN'			=> '<strong>Who Read What</strong> - Can administer the extension',
	'ACL_A_WRW_METRICS'			=> '<strong>Who Read What</strong> - Can see the metrics',

	'ACL_U_WRW_METRICS'			=> '<strong>Who Read What</strong> - Can see the metrics',
	'ACL_U_WRW_USER'			=> '<strong>Who Read What</strong> - Can use the extension',
));
