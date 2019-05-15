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
	'ACL_CAT_PHPBB_STUDIO'	=> 'phpBB Studio',

	// Modules
	'ACP_WRW_CAT'				=> 'phpBB Studio - Who Read What',
	'ACP_WRW_SETTINGS'			=> 'Settings',
	'ACP_WRW_SETTINGS_TITLE'	=> 'Mark as read settings',

	// Settings
	'ACP_WRW_SETTINGS_PAGE_T'	=> 'phpBB Studio - Who Read What',
	'ACP_WRW_READ_ACTIVE'		=> 'Status',
	'ACP_WRW_READ_ACTIVE_DESC'	=> 'NO will override the Forum settings. Basically is like disabling the extension but keeps the data and the metrics.',
	'ACP_WRW_READ_INT'			=> 'Interval',
	'ACP_WRW_READ_INT_DESC'		=> 'How often the jQuery function has to run to check what posts are in view. Has to be between 1 and 10.',
	'ACP_WRW_READ_PCT'			=> 'Percentage',
	'ACP_WRW_READ_PCT_DESC'		=> 'How much of the post has to be visible to consider it as currently in view. Has to be between 50 and 100.',
	'ACP_WRW_READ_PCT_PERCENT'	=> 'Percent',
	'ACP_WRW_READ_CPW'			=> 'Length of a word',
	'ACP_WRW_READ_CPW_DESC'		=> 'The <strong>%1$saverage length of words for your language%2$s</strong>, used to determine how many words there are in a post. Has to be between 4 and 15.',
	'ACP_WRW_READ_CHARS'		=> 'Characters',
	'ACP_WRW_READ_WPM'			=> 'Words per minute',
	'ACP_WRW_READ_WPM_DESC'		=> 'The amount of words the user reads at, used to determine how long a post should be in view to consider it as fully read. Has to be between 50 and 500.',
	'ACP_WRW_READ_WORDS'		=> 'Words',
	'ACP_WRW_READ_SEQ'			=> 'Sequential reading',
	'ACP_WRW_READ_SEQ_DESC'		=> 'This means only 1 post at a time is considered in view and being read, even though there might be multiple. Sequential reading is top to bottom.',

	// Forums
	'ACP_FORUM_WRW_READ'		=> 'Mark posts as read',
	'ACP_FORUM_WRW_READ_DESC'	=> 'Whether or not we should track if users have read a post or not.',

	// Log
	'LOG_ACP_WRW_SETTINGS'		=> '<strong>Who Read What</strong> Altered extension settings.',
));
