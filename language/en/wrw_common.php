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
	// Read controller
	'WRW_ERROR_CAN_NOT'			=> 'You <strong>do not</strong> have the permissions to access this page.',
	'WRW_ERROR_NO_FORUM'		=> 'Forum not found!',
	// Common
	'WRW_READ'					=> 'Read',
	'WRW_READ_POSTS'			=> 'Read posts',
	'WRW_VIEW_POST_READ'		=> 'Get a list of all users that read this specific post.',
	'WRW_VIEW_TOPIC_READ'		=> 'Get a list of all users that read a post in this topic.',

	'WRW_AUTO'					=> '(auto)',
	'WRW_AUTO_FULL'				=> 'automatically',
	'WRW_MAN'					=> '(man)',
	'WRW_MAN_FULL'				=> 'manually',
	'WRW_PERCENTAGE'			=> 'Percentage',
	'WRW_VIEW_FORUM'			=> 'View this forum',
	'WRW_VIEW_TOPIC'			=> 'View this topic',
	'WRW_VIEW_TOPIC_WRW'		=> 'View who read this topic',
	'WRW_READ_PERCENTAGE'		=> 'Read percentage',
	'WRW_READ_TIME'				=> 'Read time',
	'WRW_READ_USERS'			=> 'Read by users',
	'WRW_USER_READ_POSTS'		=> 'Search user’s read posts',

	'WRW_POST_READ_NOT'			=> 'This post has not been%1$s marked read.', // No space after %s: replaced by "automatically " or "manually "
	'WRW_POST_READ_USERS'		=> array(
		1 => 'This post has been read by %d user',
		2 => 'This post has been read by %d users',
	),

	// Explanation
	'WRW_INFO_COLUMN'			=> 'The <strong>read posts</strong> column shows how many posts (%1$s), what percentage of posts (%2$s) and the time of the last read post (%3$s).',
	'WRW_INFO_COLUMN_HP'		=> '<strong>Highlight Post:</strong> You can sort by manually <em>(man)</em> read statistics.',
	'WRW_INFO_COLUMN_WRW'		=> '<strong>Who Read What:</strong> You can sort by automatically %sread statistics.', // No space after %s: replaced by "(auto) "
	'WRW_INFO_ICON'				=> 'The <strong>icon</strong> (%1$s/%2$s) in front of a forum indicates if this forum <em>(or a subforum)</em> has <strong>Who Read What</strong> enabled or not.',
	'WRW_INFO_READ'				=> 'Posts with a <strong>light-azure</strong> background have been <em>automatically</em> read.',
	'WRW_INFO_PANEL_HP'			=> '<strong>Highlight Posts:</strong> The bottom panel on the right of the posts shows if it was <em>manually</em> marked read.',
	'WRW_INFO_PANEL_WRW'		=> '<strong>Who Read What:</strong> The top panel on the right of the post shows if it was <em>automatically</em> marked read.',
	'WRW_INFO_ICONS_GROUP'		=> '%1$s Post has been read by at least one user. &nbsp; %2$s Read by this many users. &nbsp; %3$s Last read at this time by a user.',
	'WRW_INFO_ICONS_USER'		=> '%1$s Post has been read. &nbsp; %2$s Read by this user. &nbsp; %3$s Read at this time.',

	// Metrics
	'WRW_CAT'					=> 'Who Read What',
	'WRW_TOPIC'					=> 'Topic',
	'WRW_DATE'					=> 'Date',
	'WRW_BY'					=> 'By',
	'WRW_VIEWS'					=> 'Views',
	'WRW_POSTS'					=> 'Posts',
	'WRW_READ_AT'				=> '(auto) read at',
	'WRW_READ_TOTAL'			=> 'Total read',
	'WRW_TOPIC_PERCENT'			=> 'True',

	'WRW_POST_LIST_PAGE'			=> 'Time for this post each user represented here has automatically read.',
	'WRW_HLPOSTS_POST_LIST_PAGE'	=> 'Only those who have automatically read the post are represented in this list which also contains the note of the manual reading confirmation if available.',
	'WRW_TOPIC_LIST_PAGE'			=> 'How many posts of this topic each user represented here has automatically read.',

	'WRW_MARKED_HLPOSTS'		=> '(man) read at',
	'WRW_NO_HLPOSTS'			=> 'unavailable',
	'WRW_RETURN_TO_POST'		=> 'Return to post',

	'WRW_USERS_LISTING'	=> array(
		0	=> 'WRW Metrics',
		1	=> 'WRW Metrics &bull; page %d',
		2	=> 'WRW Metrics &bull; page %d',
	),

	'WRW_LIST_COUNT'	=> array(
		1	=> ' Found a total of <strong>%d</strong> user',
		2	=> ' Found a total of <strong>%d</strong> users',
	),

	// Translators please do not change the following line, no need to translate it!
	'PHPBBSTUDIO_WRW_CREDIT_LINE'		=> '<a href="https://phpbbstudio.com">Who Read What</a> &copy; 2019 - phpBB Studio',
));
