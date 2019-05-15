<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Memberlist listener.
 */
class memberlist_listener implements EventSubscriberInterface
{
	/**
	 * Assign functions defined in this class to event listeners in the core.
	 *
	 * @static
	 * @return array
	 * @access public
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_modify_viewprofile_sql'		=> 'wrw_memberlist_modify_viewprofile_sql',
			'core.memberlist_view_profile'					=> 'wrw_memberlist_view_profile',
			'core.memberlist_modify_template_vars'			=> 'wrw_memberlist_modify_template_vars',
			'core.memberlist_modify_memberrow_sql'			=> 'wrw_memberlist_modify_memberrow_sql',
			'core.memberlist_prepare_profile_data'			=> 'wrw_memberlist_prepare_profile_data',
		);
	}

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Who Read What table */
	protected $table;

	/** @var \phpbbstudio\wrw\core\functions_common */
	protected $functions;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\db\driver\driver_interface		$db			Database object
	 * @param  \phpbb\config\config						$config		Configuration object
	 * @param  \phpbb\controller\helper					$helper		Controller helper object
	 * @param  \phpbb\language\language					$lang		Language object
	 * @param  \phpbb\template\template					$template	Template object
	 * @param  \phpbb\user								$user		User object
	 * @param  string									$table		Who Read What table
	 * @param  \phpbbstudio\wrw\core\functions_common	$functions	Who Read What common functions
	 * @return void
	 * @access public
	 */
	public function	__construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config	$config, \phpbb\controller\helper $helper, \phpbb\language\language $lang, \phpbb\template\template $template, \phpbb\user $user, $table, \phpbbstudio\wrw\core\functions_common $functions)
	{
		$this->db			= $db;
		$this->config		= $config;
		$this->helper		= $helper;
		$this->lang			= $lang;
		$this->template		= $template;
		$this->user			= $user;
		$this->table		= $table;
		$this->functions	= $functions;
	}

	/**
	 * Modify user data SQL before member profile row is created.
	 *
	 * @event  core.memberlist_modify_viewprofile_sql
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_memberlist_modify_viewprofile_sql($event)
	{
		$user_id = (int) $event['user_id'];

		/**
		 * That's a necessary evil.
		 * Check if the user exists. In cases where has been deleted and somebody
		 * is trying to reach its profile IE.: clicking its username in a quoted post.
		 */
		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$is_user_id = (bool) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		if (!$is_user_id)
		{
			return;
		}

		/**
		 * Check permissions prior to run the code
		 */
		if ( $this->functions->is_authed() )
		{
			$sql_array = $event['sql_array'];

			$sql_array['SELECT'] .= ' , COUNT(wrw.post_id) as wrw_u_posts_read';

			$sql_array['LEFT_JOIN'] = array();
			$sql_array['LEFT_JOIN'][] = array(
				'FROM'	=> array($this->table => 'wrw'),
				'ON'	=> $this->db->sql_in_set('wrw.user_id', $user_id) . '
					AND wrw.user_id <> ' . ANONYMOUS
			);

			$event['sql_array'] = $sql_array;
		}
	}

	/**
	 * Adds our template vars to the viewprofile page.
	 *
	 * @event  core.memberlist_view_profile
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_memberlist_view_profile($event)
	{
		/**
		 * Check permissions prior to run the code
		 */
		if ( $this->functions->is_authed() )
		{
			$member = $event['member'];
			$user_id = (int) $member['user_id'];

			$this->template->assign_vars(array(
				'WRW_U_TOTAL'	=> (int) $member['wrw_u_posts_read'],

				'S_WRW_VIEW'	=> (bool) $this->functions->has_perm_metrics(),
				'U_WRW_VIEW'	=> $this->helper->route('phpbbstudio_wrw_read_usergroup', array('mode' => 'user', 'id' => (int) $user_id)),
			));
		}
	}

	/**
	 * Adds our template variables to the memberlist.
	 *
	 * @event  core.memberlist_modify_template_vars
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_memberlist_modify_template_vars($event)
	{
		/**
		 * Check permissions prior to run the code
		 */
		if ( $this->functions->is_authed() )
		{
			$template_vars = $event['template_vars'];

			$template_vars += array(
				'WRW_READ_POSTS'	=> $this->lang->lang('WRW_CAT'),
				'S_WRW_VIEW'		=> (bool) $this->functions->has_perm_metrics(),
			);

			$event['template_vars'] = $template_vars;
		}
	}

	/**
	 * Modify user data SQL before member row is created.
	 *
	 * @event  core.memberlist_modify_memberrow_sql
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_memberlist_modify_memberrow_sql($event)
	{
		/**
		 * Check permissions prior to run the code
		 */
		if ( $this->functions->is_authed() )
		{
			$sql_array = $event['sql_array'];

			$user_list = $event['user_list'];

			if ($event['mode'] == 'group')
			{
				$sql_array['SELECT'] .= ' , COUNT(wrw.post_id) as wrw_u_posts_read';

				$sql_array['LEFT_JOIN'] = array();
				$sql_array['LEFT_JOIN'][] = array(
					'FROM'	=> array($this->table => 'wrw'),
					'ON'	=> $this->db->sql_in_set('wrw.user_id', $user_list) . '
						AND wrw.user_id = u.user_id
						AND wrw.user_id <> ' . ANONYMOUS
				);

				$sql_array['WHERE'] .= ' GROUP BY u.user_id, ug.group_leader';
			}
			else
			{
				$sql_array['SELECT'] .= ' , COUNT(wrw.post_id) as wrw_u_posts_read';

				$sql_array['LEFT_JOIN'] = array();
				$sql_array['LEFT_JOIN'][] = array(
					'FROM'	=> array($this->table => 'wrw'),
					'ON'	=> $this->db->sql_in_set('wrw.user_id', $user_list) . '
						AND wrw.user_id = u.user_id
						AND wrw.user_id <> ' . ANONYMOUS
				);

				$sql_array['WHERE'] .= ' GROUP BY u.user_id ORDER BY u.user_id';
			}

			$event['sql_array'] = $sql_array;
		}
	}

	/**
	 * Adds our data to the memberlist's memberrow.
	 *
	 * @event  core.memberlist_prepare_profile_data
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_memberlist_prepare_profile_data($event)
	{
		/**
		 * Check permissions prior to run the code
		 */
		if ( $this->functions->is_authed() )
		{
			$template_data = $event['template_data'];

			$template_data += array(
				'U_WRW_READ_POSTS'	=> $this->helper->route('phpbbstudio_wrw_read_usergroup', array('mode' => 'user', 'id' => (int) $event['data']['user_id'])),
				'WRW_TOTAL'			=> (int) $event['data']['wrw_u_posts_read'],
			);

			$event['template_data'] = $template_data;
		}
	}
}
