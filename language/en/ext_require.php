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
	'ERROR_PHPBB_VERSION'	=> 'Minimum phpBB version required is %1$s but less than %2$s',
	'ERROR_PHP_VERSION'		=> 'Minimum PHP version required is %1$s',
));
