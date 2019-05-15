<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\controller;

/**
 * WRW Topic & Post controller
 */
class read_controller
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var string Who Read What database table */
	protected $table;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string PHP file extension */
	protected $php_ext;

	/** @var \phpbbstudio\wrw\core\functions_common */
	protected $functions;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var string Database table prefix */
	protected $table_prefix;

	/** @var \phpbb\textformatter\s9e\renderer */
	protected $renderer;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\auth\auth							$auth			Authentication object
	 * @param  \phpbb\db\driver\driver_interface		$db				Database object
	 * @param  \phpbb\config\config						$config			Configuration object
	 * @param  \phpbb\controller\helper					$helper			Controller helper object
	 * @param  \phpbb\request\request					$request		Request object
	 * @param  \phpbb\template\template					$template		Template object
	 * @param  \phpbb\user								$user			User object
	 * @param  \phpbb\language\language					$lang			Language object
	 * @param  string									$table			Who Read What database table
	 * @param  string									$root_path		phpBB root path
	 * @param  string									$php_ext		PHP file extension
	 * @param  \phpbbstudio\wrw\core\functions_common	$functions		Who Read What common functions
	 * @param  \phpbb\pagination						$pagination		Pagination object
	 * @param  string									$table_prefix	This database's table prefix
	 * @param  \phpbb\textformatter\s9e\renderer		$renderer		Text formatter renderer object
	 * @return void
	 * @access public
	 */
	public function __construct(
		\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config, \phpbb\controller\helper $helper,
		\phpbb\request\request $request, \phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\language\language $lang,
		$table, $root_path, $php_ext,
		\phpbbstudio\wrw\core\functions_common $functions,
		\phpbb\pagination $pagination,
		$table_prefix,
		\phpbb\textformatter\s9e\renderer $renderer
	)
	{
		$this->auth			= $auth;
		$this->db			= $db;
		$this->config		= $config;
		$this->helper		= $helper;
		$this->request		= $request;
		$this->template		= $template;
		$this->user			= $user;
		$this->lang			= $lang;
		$this->table		= $table;
		$this->root_path	= $root_path;
		$this->php_ext		= $php_ext;
		$this->functions	= $functions;
		$this->pagination	= $pagination;
		$this->table_prefix	= $table_prefix;
		$this->renderer		= $renderer;
	}

	/**
	 * Mark a post as "read" in the Who Read What table.
	 *
	 * @return void		\phpbb\json_response
	 * @access public
	 */
	public function mark()
	{
		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', array(
			'forum_id'	=> (int) $this->request->variable('forum', 0),
			'topic_id'	=> (int) $this->request->variable('topic', 0),
			'post_id'	=> (int) $this->request->variable('post', 0),
			'user_id'	=> (int) $this->user->data['user_id'],
			'read_time'	=> (int) time(),
		));
		$this->db->sql_query($sql);

		$this->template->set_filenames(array('wrw_read' => '@phpbbstudio_wrw/wrw_read.html'));

		$this->template->assign_vars(array(
			'S_WRW_VIEW'	=> (bool) $this->functions->has_perm_metrics(),
			'WRW_READ_TIME'	=> $this->user->format_date(time()),
			// @todo: not yet in use
			'S_WRW_USER'	=> (bool) $this->functions->is_authed(),
		));

		$json_response = new \phpbb\json_response;

		$json_response->send(array(
			'tpl'	=> $this->template->assign_display('wrw_read'),
		));
	}

	/**
	 * Create a list of users who read the post(s), depending on the mode.
	 *
	 * @param  int	$mode		topic|post
	 * @param  int	$id			corresponding identifier
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @access public
	 */
	public function view($mode, $id)
	{
		/* Check permissions */
		if (!$this->functions->has_perm_metrics())
		{
			throw new \phpbb\exception\http_exception(403, $this->lang->lang('WRW_ERROR_CAN_NOT'));
		}

		/* These are for pagination */
		$start = $this->request->variable('start', 0);
		$limit = (int) $this->config['posts_per_page'];

		switch ($mode)
		{
			case 'topic':
				$this->template->assign_vars(array(
						'WRW_IN_TOPIC_MODE'	=> true,
						'S_IS_HLPOSTS'		=> (bool) $this->functions->is_hlposts_enabled(),
					)
				);

				/* Topic data */
				$this->topic_data($id);

				/* Get forum id from topic ID */
				$sql = 'SELECT forum_id
						FROM ' . TOPICS_TABLE . '
						WHERE topic_id = ' . (int) $id;
				$result = $this->db->sql_query_limit($sql, 1);
				$forum_id = $this->db->sql_fetchfield('forum_id');
				$this->db->sql_freeresult($result);

				/* Check if forum_wrw_read is enabled for the forum this topic belongs to */
				if ( !$this->functions->forum_enabled( (int) $forum_id) )
				{
					throw new \phpbb\exception\http_exception(404, $this->lang->lang('WRW_ERROR_NO_FORUM'));
				}

				/* Get a list of all users which read a post in this topic */
				$sql = 'SELECT user_id
					FROM ' . $this->table . '
					WHERE topic_id = ' . (int) $id . '
						AND user_id <> ' . ANONYMOUS . '
					GROUP BY user_id
					ORDER BY MAX(read_time) ASC';
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrowset($result);
				$total_users = count($row);
				$this->db->sql_freeresult($result);
				/* No need of this any more */
				unset($row);

				/* Declare the array as necessary evil */
				$posts = array();

				/* Get posts list */
				$sql = 'SELECT post_id
						FROM ' . POSTS_TABLE . '
						WHERE topic_id = ' . (int) $id;
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$posts[] = (int) $row['post_id'];
				}
				$this->db->sql_freeresult($result);

				/* User list */
				$this->user_list($id, $mode, $posts, $limit, $start);

				/* Breadcrumbs */
				$this->wrw_breadcrumbs($forum_id);
			break;

			case 'post':
				$this->template->assign_vars(array(
					'WRW_IN_POST_MODE'	=> true,
					'S_IS_HLPOSTS'		=> (bool) $this->functions->is_hlposts_enabled(),
				));

				/* Get forum id from post_id */
				$sql = 'SELECT forum_id
						FROM ' . POSTS_TABLE . '
						WHERE post_id = ' . (int) $id;
				$result = $this->db->sql_query_limit($sql, 1);
				$forum_id = $this->db->sql_fetchfield('forum_id');
				$this->db->sql_freeresult($result);

				/* Check if forum_wrw_read is enabled for the forum this post belongs to */
				if (!$this->functions->forum_enabled( (int) $forum_id))
				{
					throw new \phpbb\exception\http_exception(404, $this->lang->lang('WRW_ERROR_NO_FORUM'));
				}

				/* Get topic id */
				$sql = 'SELECT topic_id
						FROM ' . POSTS_TABLE . '
						WHERE post_id = ' . (int) $id;
				$result = $this->db->sql_query_limit($sql, 1);
				$topic_id = $this->db->sql_fetchfield('topic_id');
				$this->db->sql_freeresult($result);

				/* Get a list of all users which read Teh post */
				$sql = 'SELECT user_id
						FROM ' . $this->table . '
						WHERE post_id = ' . (int) $id . '
							AND user_id <> ' . ANONYMOUS;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrowset($result);
				$total_users = count($row);
				$this->db->sql_freeresult($result);
				/* No need of this any more */
				unset($row);

				/* Topic data */
				$this->topic_data($topic_id);

				/* Post data */
				$this->post_data($id);

				/* Breadcrumbs */
				$this->wrw_breadcrumbs($forum_id);

				/* User list */
				$this->user_list($id, $mode, array($id), $limit, $start);
			break;
		}

		$template_vars = array(
			'TOTAL_USERS'	=> $this->lang->lang('WRW_LIST_COUNT', (int) $total_users),
		);
		$this->template->assign_vars($template_vars);

		$url = $this->helper->route('phpbbstudio_wrw_read_list', array('mode' => $mode, 'id' => (int) $id));
		$this->pagination->generate_template_pagination($url, 'pagination', 'start', $total_users, $limit, $start);
		$name = $this->lang->lang('WRW_USERS_LISTING', $this->pagination->get_on_page($limit, $start));

		make_jumpbox(append_sid("{$this->root_path}viewforum.{$this->php_ext}"));

		return $this->helper->render('@phpbbstudio_wrw/wrw_list.html', $name);
	}

	/**
	 * Assigns the user list to the template.
	 *
	 * @param  int		$id			topic ID
	 * @param  string	$mode		topic|user|post
	 * @param  array	$posts		The post IDs
	 * @param  int		$limit		The query limit for pagination
	 * @param  int		$start		The start for pagination
	 * @return void
	 * @access protected
	 */
	protected function user_list($id, $mode, $posts, $limit, $start)
	{
		if ($this->functions->is_hlposts_enabled() && ($mode === 'post'))
		{
			$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, wrw.read_time, MAX(pr.read_time) AS hlpread
					FROM ' . USERS_TABLE . ' u, ' . $this->table . ' wrw
					LEFT JOIN ' . $this->table_prefix . 'post_read' . ' pr
						ON (
							' . $this->db->sql_in_set('pr.post_id', $posts) . '
							AND wrw.user_id = pr.user_id
						)
					WHERE ' . $this->db->sql_in_set('wrw.post_id', $posts) . '
						AND u.user_id <> ' . ANONYMOUS . '
						AND wrw.user_id = u.user_id
					GROUP BY u.user_id
					ORDER BY wrw.read_time ASC';
			$result = $this->db->sql_query_limit($sql, $limit, $start);

			while ($user = $this->db->sql_fetchrow($result))
			{
				/* Map arguments for phpbb_get_avatar() */
				$row_avatar = $this->functions->users_array_row_avatar($user);

				$no_avatar = $this->functions->wrw_no_avatar();

				/**
				 * DAE (Default Avatar Extended) extension compatibility
				 */
				$user_av = $this->functions->is_dae($row_avatar, $user, $no_avatar);

				/* Map arguments for the template */
				$users_array = $this->functions->users_array_tpl_vars($user, $user_av);

				/* Map additional arguments for HLPOSTS extension */
				$users_array +=	array(
					'MARKED_HLP'	=> isset($user['hlpread']) ? $this->user->format_date($user['hlpread'], $this->config['wrw_format_date']) : '',
				);

				$this->template->assign_block_vars('users', $users_array);
			}

			$this->db->sql_freeresult($result);
		}
		else if ($this->functions->is_hlposts_enabled() && ($mode === 'topic'))
		{
			$tot_posts = (int) $this->functions->tot_posts($id);

			$sql_ary = array(
				'SELECT'	=> 'wrw.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, COUNT(wrw.post_id) as total',

				'FROM'		=> array(
					$this->table	=> 'wrw',
					USERS_TABLE		=> 'u',
				),

				'WHERE'		=> 'wrw.user_id = u.user_id AND wrw.user_id <> ' . ANONYMOUS . ' AND ' . $this->db->sql_in_set('wrw.post_id', $posts),
			);

			$sql_ary['GROUP_BY'] ='wrw.user_id,	u.username,	u.user_colour, u.user_avatar, u.user_avatar_type';

			$sql = $this->db->sql_build_query('SELECT',	$sql_ary);
			$result	= $this->db->sql_query_limit($sql, $limit, $start);

			while ($user = $this->db->sql_fetchrow($result))
			{
				/* Map arguments for phpbb_get_avatar() */
				$row_avatar = $this->functions->users_array_row_avatar($user);

				$no_avatar = $this->functions->wrw_no_avatar();

				/**
				 * DAE (Default Avatar Extended) extension compatibility
				 * Here we do not care about the UCP prefs -> view avatars
				 */
				$user_av = $this->functions->is_dae($row_avatar, $user,	$no_avatar);

				/* Map arguments for the template */
				$users_array = $this->functions->users_array_tpl_vars($user, $user_av);

				if ($mode === 'topic')
				{
					/* Map additional arguments for percentage */
					$users_array += $this->functions->users_array_percentage($tot_posts, $user['total']);
				}

				$this->template->assign_block_vars('users', $users_array);
			}
			$this->db->sql_freeresult($result);
		}
		/* WRW in action alone */
		else
		{
			if ($mode === 'topic')
			{
				$tot_posts = (int) $this->functions->tot_posts($id);
			}

			/* Get users list */
			$sql_ary = array(
				'SELECT'	=> 'wrw.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type',

				'FROM'		=> array(
					$this->table	=> 'wrw',
					USERS_TABLE		=> 'u',
				),

				'WHERE'		=> 'wrw.user_id = u.user_id AND wrw.user_id <> ' . ANONYMOUS . ' AND ' . $this->db->sql_in_set('wrw.post_id', $posts),
			);

			$sql_ary['SELECT'] .= ($mode === 'topic') ? ', COUNT(wrw.post_id) as total' : ', wrw.read_time';

			if ($mode === 'topic')
			{
				$sql_ary['GROUP_BY'] ='wrw.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type';
			}

			$sql = $this->db->sql_build_query('SELECT', $sql_ary);
			$result	= $this->db->sql_query_limit($sql, $limit, $start);

			while ($user = $this->db->sql_fetchrow($result))
			{
				/* Map arguments for phpbb_get_avatar()	*/
				$row_avatar = $this->functions->users_array_row_avatar($user);

				$no_avatar = $this->functions->wrw_no_avatar();

				/**
				 * DAE (Default Avatar Extended) extension compatibility
				 * Here we do not care about the UCP prefs -> view avatars
				 */
				$user_av = $this->functions->is_dae($row_avatar, $user, $no_avatar);

				/* Map arguments for the template */
				$users_array = $this->functions->users_array_tpl_vars($user, $user_av);

				if ($mode === 'topic')
				{
					$users_array += $this->functions->users_array_percentage($tot_posts, $user['total']);
				}
				$this->template->assign_block_vars('users', $users_array);
			}

			$this->db->sql_freeresult($result);
		}
	}

	/**
	 * Assigns the topic data to the template.
	 *
	 * @param  int		$id		The topic ID
	 * @return void
	 * @access protected
	 */
	protected function topic_data($id)
	{
		/* Get topic data */
		$sql = 'SELECT t.*, f.forum_name
				FROM ' . TOPICS_TABLE . ' t
				JOIN ' . FORUMS_TABLE . ' f
					ON t.forum_id = f.forum_id
				WHERE t.topic_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql, 1);
		$topic = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'TOPIC_TITLE'	=> $topic['topic_title'],
			'TOPIC_USER'	=> get_username_string('full', $topic['topic_poster'], $topic['topic_first_poster_name'], $topic['topic_first_poster_colour']),
			'TOPIC_TIME'	=> $this->user->format_date($topic['topic_time'], $this->config['wrw_format_date']),
			'TOPIC_POSTS'	=> $topic['topic_posts_approved'],
			'TOPIC_VIEWS'	=> $topic['topic_views'],

			'U_TOPIC'		=> append_sid($this->root_path . 'viewtopic.' . $this->php_ext, array('f' => (int) $topic['forum_id'], 't' => (int) $topic['topic_id'])),
		));
	}

	/**
	 * Assigns the post data to the template
	 *
	 * @param  int		$id		The post ID
	 * @return void
	 * @access protected
	 */
	protected function post_data($id)
	{
		/* Grab the post data */
		$sql_ary = array(
			'SELECT'	=> 't.topic_title, t.topic_id, p.forum_id, p.post_subject, p.post_text, p.post_time, p.post_attachment, u.user_id, u.username, u.user_colour',
			'FROM'		=> array(
				POSTS_TABLE		=> 'p',
				USERS_TABLE		=> 'u',
				TOPICS_TABLE	=> 't',
			),
			'WHERE'		=> 'p.poster_id = u.user_id
				AND p.post_id = ' . (int) $id,
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$post = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		/* First let's render the text */
		$censor = censor_text($post['post_text']);
		$text = $this->renderer->render($censor);

		if ( $this->auth->acl_get('u_download') && $this->config['allow_attachments'] && $this->auth->acl_get('f_download', $post['forum_id']) )
		{
			/**
			 * Attachments - Include files needed for display attachments
			 */
			if ( !function_exists('parse_attachments') )
			{
				/** @noinspection PhpIncludeInspection */
				include $this->root_path . 'includes/functions_content.' . $this->php_ext;
			}

			if ($post['post_attachment'])
			{
				$attachments = array();

				$sql = 'SELECT *
						FROM ' . ATTACHMENTS_TABLE . '
						WHERE post_msg_id = ' . (int) $id . '
							AND in_message = 0
						ORDER BY attach_id DESC';
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$attachments[] = $row;
				}
				$this->db->sql_freeresult($result);

				if (count($attachments))
				{
					$update_count =	array();

					/* Only parses attachments placed inline */
					parse_attachments((int) $post['forum_id'], $text, $attachments, $update_count);
				}

				/* Display the remaining of the attachments */
				if (count($attachments))
				{
					foreach ($attachments as $attachment)
					{
						$this->template->assign_block_vars('attachments', array(
							'POST_ATTACHMENTS' => $attachment,
							)
						);
					}
				}
			}
		}

		/* Set up post URL */
		$post_url = append_sid($this->root_path . 'viewtopic.' . $this->php_ext, "f={$post['forum_id']}&amp;p={$id}#p{$id}", false);

		/* Assign template variables */
		$this->template->assign_vars(array(
			'POST_AUTHOR'		=> get_username_string('full', $post['user_id'], $post['username'],	$post['user_colour']),
			'POST_SUBJECT'		=> $post['post_subject'],
			'POST_TEXT'			=> $text,
			'POST_TIME'			=> $this->user->format_date($post['post_time'], $this->config['wrw_format_date']),
			'U_POST'			=> $post_url,
		));
	}

	/**
	 * Assigns the breadcrumbs to the template.
	 *
	 * @param  int		$forum_id		The	forum ID
	 * @return void
	 * @access protected
	 */
	protected function wrw_breadcrumbs($forum_id)
	{
		/**
		 * Breadcrumbs
		 *
		 * Grab forum data
		 */
		$sql = 'SELECT *
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
		$result = $this->db->sql_query($sql);
		$forum_data_ary = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		/**
		 * And include files needed for to create the forum navigation links for the given forum
		 */
		if ( !function_exists('generate_forum_nav') )
		{
			/** @noinspection PhpIncludeInspection */
			include $this->root_path . 'includes/functions_display.' . $this->php_ext;
		}

		generate_forum_nav($forum_data_ary);
	}
}
