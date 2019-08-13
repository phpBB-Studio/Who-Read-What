<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\core;

/**
 * Common functions.
 */
class functions_common
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var bool If the Highlight Posts extension is enabled */
	protected $is_hlposts_enabled;

	/** @var bool If the Default Avatar Extended extension is enabled */
	protected $is_dae_enabled;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\auth\auth							$auth			Authentication object
	 * @param  \phpbb\config\config						$config			Configuration object
	 * @param  \phpbb\db\driver\driver_interface		$db				Database object
	 * @param  \phpbb\user								$user			User object
	 * @param  \phpbb\extension\manager					$ext_manager	extension manager
	 * @return void
	 * @access public
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\extension\manager $ext_manager)
	{
		$this->auth			= $auth;
		$this->config		= $config;
		$this->db			= $db;
		$this->user			= $user;

		$this->is_hlposts_enabled	= (bool) $ext_manager->is_enabled('threedi/hlposts');
		$this->is_dae_enabled		= (bool) $ext_manager->is_enabled('threedi/dae');
	}

	/**
	 * Returns whether the DAE is enabled and follows some conditions.
	 *
	 * @return bool
	 * @access public
	 */
	public function is_dae_enabled()
	{
		return $this->is_dae_enabled && $this->config['threedi_default_avatar_extended'] && ($this->auth->acl_get('u_dae_user') || $this->auth->acl_get('a_dae_admin'));
	}

	/**
	 * Returns whether the DAE is enabled and follows some conditions.
	 *
	 * @return bool
	 * @access public
	 */
	public function is_hlposts_enabled()
	{
		return (bool) $this->is_hlposts_enabled;
	}

	/**
	 * Returns default no avatar.
	 *
	 * @return string		HTML formatted string with default no avatar
	 * @access public
	 */
	public function wrw_no_avatar()
	{
		$no_avatar = '<img src="' . generate_board_url() . '/styles/prosilver/theme/images/no_avatar.gif" />';

		return $no_avatar;
	}

	/**
	 * Returns the avatar for a given user.
	 *
	 * @param  array		$row_avatar		The avatar array with arguments
	 * @param  array		$user			The user array with arguments
	 * @param  string		$no_avatar		The user NO-Avatar string
	 * @return string						The avatar for a given user with DAE compatibility
	 * @access public
	 */
	public function is_dae($row_avatar, $user, $no_avatar)
	{
		/**
		 * DAE (Default Avatar Extended) extension compatibility
		 * Here we do not care about the UCP prefs -> view avatars
		 */
		if ( $this->is_dae_enabled() )
		{
			$user_av = phpbb_get_avatar($row_avatar, '');
		}
		else
		{
			$user_av = (!empty($user['user_avatar'])) ? phpbb_get_avatar($row_avatar, '') : ( $no_avatar ? $no_avatar : '' );
		}

		return $user_av;
	}

	/**
	 * Returns the common template variables to use in different places.
	 *
	 * @param  array		$user		The user array
	 * @param  string		$user_av	The user avatar string
	 * @return array					The common template variables
	 * @access public
	 */
	public function users_array_tpl_vars($user, $user_av)
	{
		$users_array = array(
			'NAME'			=> get_username_string('full', $user['user_id'], $user['username'], $user['user_colour']),
			'PLAIN_NAME'	=> $user['username'],
			'AVATAR'		=> $user_av,
			'TOTAL'			=> isset($user['total']) ? (int) $user['total'] : 0,
			'TIME'			=> isset($user['read_time']) ? $this->user->format_date($user['read_time'], $this->config['wrw_format_date']) : '',
		);

		return (array) $users_array;
	}

	/**
	 * Returns the common extra topic's template variables to use in different places.
	 *
	 * @param  int		$tot_posts		Total posts in the topic
	 * @param  int		$user_total		Total posts read by the user
	 * @return array					The common extra template variables
	 * @access public
	 */
	public function users_array_percentage($tot_posts, $user_total)
	{
		$percent = ((int) $user_total < 1) ? 0 : min(100, ((int) $user_total / (int) $tot_posts)) * 100;
		$degrees = (360 * $percent) / 100;
		$start = 90;

		$users_array_percentage = array(
			'TOPIC_PERCENT'		=> number_format((float) $percent),
			'DEGREE'			=> $percent > 50 ? $degrees - $start : $degrees + $start,

			'TRUE_PERCENT'		=> number_format($percent, 2),

			'S_WRW_AVAILABLE'	=> ((int) $tot_posts < 1) ? false : true,
		);

		return (array) $users_array_percentage;
	}

	/**
	 * Returns the row_avatar to use in different places.
	 *
	 * @param  array		$user		The user array
	 * @return array					The row_avatar
	 * @access public
	 */
	public function users_array_row_avatar($user)
	{
		/* Map arguments for phpbb_get_avatar() */
		$row_avatar = array(
			'avatar'		 => $user['user_avatar'],
			'avatar_type'	 => $user['user_avatar_type'],
			'avatar_height'	 => 32,
			'avatar_width'	 => '',
		);

		return (array) $row_avatar;
	}

	/**
	 * Returns whether the user has permission to see the square check in viwtopic.
	 *
	 * @return bool
	 * @access public
	 */
	public function has_perm_check()
	{
		return ($this->auth->acl_get('u_wrw_check') || $this->auth->acl_get('a_wrw_admin') || $this->auth->acl_get('a_wrw_metrics'));
	}


	/**
	 * Returns whether the user has permission to see the metrics.
	 *
	 * @return bool
	 * @access public
	 */
	public function has_perm_metrics()
	{
		return ($this->auth->acl_get('u_wrw_metrics') || $this->auth->acl_get('a_wrw_admin') || $this->auth->acl_get('a_wrw_metrics'));
	}

	/**
	 * Returns whether the user is authenticated.
	 *
	 * @return bool
	 * @access public
	 */
	public function is_authed()
	{
		return ($this->auth->acl_get('u_wrw_user') || $this->auth->acl_get('a_wrw_admin') || $this->auth->acl_get('a_wrw_metrics'));
	}

	/**
	 * Check if the WRW extension is enabled for a specific forum.
	 *
	 * @param  int		$forum_id		The forum identifier
	 * @return bool						Whether or not the extension is enabled for this forum
	 * @access public
	 */
	public function forum_enabled($forum_id)
	{
		if (empty($forum_id))
		{
			return false;
		}

		$sql = 'SELECT forum_wrw_read
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$s_enabled = $this->db->sql_fetchfield('forum_wrw_read');
		$this->db->sql_freeresult($result);

		return (bool) $s_enabled;
	}

	/**
	 * Counts approved posts in a topic.
	 *
	 * @param  int		$id				The topic identifier
	 * @return int		$total			The amount of approved posts
	 * @access public
	 */
	public function tot_posts($id)
	{
		$sql = 'SELECT topic_posts_approved
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$total = $this->db->sql_fetchfield('topic_posts_approved');
		$this->db->sql_freeresult($result);

		return (int) $total;
	}

	/**
	 * Counts read posts in the WRW table.
	 *
	 * @param  string	$table			The database table to query
	 * @param  int		$user_id		The user identifier
	 * @return int		$total			The amount of read posts
	 * @access public
	 */
	public function total_wrw_read_posts($table, $user_id)
	{
		$sql = 'SELECT COUNT(post_id) as total
			FROM ' . $table . '
			WHERE user_id = ' . (int) $user_id . '
			GROUP BY user_id';
		$result = $this->db->sql_query($sql);
		$total = $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return (int) $total;
	}
}
