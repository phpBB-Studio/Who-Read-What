<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\controller;

/**
 * WRW User & Group controller
 */
class view_controller
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\content_visibility */
	protected $con_vis;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbbstudio\wrw\core\functions_common */
	protected $functions;

	/** @var \phpbb\group\helper */
	protected $group_helper;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\textformatter\s9e\utils */
	protected $utils;

	/** @var string		Who Read What database table */
	protected $table;

	/** @var string		Database table prefix */
	protected $table_prefix;

	/** @var string		phpBB root path */
	protected $root_path;

	/** @var string		php File extension */
	protected $php_ext;

	/** @var array		Array containing topic icons */
	protected $icons = array(
		POST_NORMAL		=> array(
			ITEM_LOCKED		=> 'lock',
			ITEM_MOVED		=> 'share',
		),
		POST_STICKY 	=> 'thumb-tack',
		POST_ANNOUNCE	=> 'bullhorn',
		POST_GLOBAL		=> 'bullhorn',
	);

	/** @var string		WRW mode (user|group) */
	protected $mode;

	/** @var int		WRW mode id (user_id|group_id) */
	protected $id;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\auth\auth							$auth			Authentication object
	 * @param  \phpbb\config\config						$config			Configuration object
	 * @param  \phpbb\content_visibility				$con_vis		Content Visibility object
	 * @param  \phpbb\db\driver\driver_interface		$db				Database object
	 * @param  \phpbbstudio\wrw\core\functions_common	$functions		WRW Common functions
	 * @param  \phpbb\group\helper						$group_helper	Group helper object
	 * @param  \phpbb\controller\helper					$helper			Controller helper object
	 * @param  \phpbb\language\language					$lang			Language object
	 * @param  \phpbb\pagination						$pagination		Pagination object
	 * @param  \phpbb\request\request					$request		Request object
	 * @param  \phpbb\template\template					$template		Template object
	 * @param  \phpbb\user								$user			User object
	 * @param  \phpbb\textformatter\s9e\utils			$utils			Textformatter utilities object
	 * @param  string									$table			WRW Database table
	 * @param  string									$table_prefix	Database table prefix
	 * @param  string									$root_path		phpBB root path
	 * @param  string									$php_ext		php File extension
	 * @return void
	 * @access public
	 */
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\content_visibility $con_vis,
		\phpbb\db\driver\driver_interface $db,
		\phpbbstudio\wrw\core\functions_common $functions,
		\phpbb\group\helper $group_helper,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $lang,
		\phpbb\pagination $pagination,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\textformatter\s9e\utils $utils,
		$table,
		$table_prefix,
		$root_path,
		$php_ext
	)
	{
		$this->auth			= $auth;
		$this->config		= $config;
		$this->con_vis		= $con_vis;
		$this->db			= $db;
		$this->functions	= $functions;
		$this->group_helper	= $group_helper;
		$this->helper		= $helper;
		$this->lang			= $lang;
		$this->pagination	= $pagination;
		$this->request		= $request;
		$this->template		= $template;
		$this->user			= $user;
		$this->utils		= $utils;
		$this->table		= $table;
		$this->table_prefix	= $table_prefix;
		$this->root_path	= $root_path;
		$this->php_ext		= $php_ext;
	}

	/**
	 * Display WRW Statistics for a specific user|group.
	 *
	 * @param  string	$mode		WRW mode (user|group)
	 * @param  int		$id			WRW mode identifier (user_id|group_id)
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @access public
	 */
	public function handle($mode, $id)
	{
		/* Make the mode and mode identifier available in this controller class. */
		$this->mode = (string) $mode;
		$this->id	= (int) $id;

		/* Requests */
		$f = $this->request->variable('f', 0);
		$t = $this->request->variable('t', 0);

		/* Booleans */
		$s_index = (!$f && !$t);
		$s_forum = ($f && !$t);
		$s_topic = !empty($t);
		$s_group = $mode === 'group';

		/* Get the data for this mode */
		$sql_select = "t.{$mode}_id, t.{$mode}_colour, t.{$mode}_rank,
						t.{$mode}_avatar, t.{$mode}_avatar_type, t.{$mode}_avatar_width, t.{$mode}_avatar_height, " .
						($s_group ? 't.group_name' : 't.username, t.user_posts');
		$sql_from = $s_group ? GROUPS_TABLE : USERS_TABLE;
		$sql_where = "{$mode}_id = " . (int) $id;

		$sql_ary = array(
			'SELECT'	=> $sql_select,
			'FROM'		=> array($sql_from => 't'),
			'WHERE'		=> $sql_where,
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		/* No user|group was found! */
		if ($data === false)
		{
			throw new \phpbb\exception\http_exception(404, $this->lang->lang('NO_' . utf8_strtoupper($mode)));
		}

		if (!function_exists('phpbb_get_user_rank'))
		{
			/** @noinspection PhpIncludeInspection */
			include $this->root_path . 'includes/functions_display.' . $this->php_ext;
		}

		/* Set up template variables for this mode */
		$wrw_name = $s_group ? $this->group_helper->get_name($data['group_name']) : $data['username'];

		$wrw_name_full = $s_group ? $this->get_groupname_string('full', $data['group_id'], $data['group_name'], $data['group_colour']) : get_username_string('full', $data['user_id'], $data['username'], $data['user_colour']);

		$wrw_avatar = $s_group ? phpbb_get_group_avatar($data) : phpbb_get_user_avatar($data);

		$rank_data = phpbb_get_user_rank($data, ($s_group ? false : $data['user_posts']));
		$wrw_rank = $rank_data['title'];
		$wrw_rank_img = $rank_data['img'];

		/* Get the groups this user is part of */
		switch ($mode)
		{
			case 'user':
				$sql = 'SELECT g.group_id, g.group_name, g.group_colour
						FROM ' . USER_GROUP_TABLE . ' ug
						LEFT JOIN ' . GROUPS_TABLE . ' g
							ON ug.group_id = g.group_id 
						WHERE ug.user_id = ' . (int) $this->id . '
						ORDER BY g.group_name';
				$result = $this->db->sql_query($sql);
				while ($group = $this->db->sql_fetchrow($result))
				{
					$this->template->assign_block_vars('groups', array(
						'NAME'		=> $this->get_groupname_string('normal', $group['group_id'], $group['group_name'], $group['group_colour']),
						'U_READ'	=> $this->helper->route('phpbbstudio_wrw_read_usergroup', array('mode' => 'group', 'id' => (int) $group['group_id'])),
					));
				}
				$this->db->sql_freeresult($result);
			break;
		}

		/* Get the user identifiers from whom we want the WRW statistics */
		$user_ids = array();

		switch ($mode)
		{
			case 'user':
				/* Just this one user */
				$user_ids[] = (int) $id;
			break;

			case 'group':
				/* Get all user id's that are in this group */
				$sql = 'SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE group_id = ' . (int) $id;
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$user_ids[] = (int) $row['user_id'];
				}
				$this->db->sql_freeresult($result);
			break;
		}

		/* WRW Base array, with forum id 0 (board index) pre-setup. */
		$wrw = array(
			'forum' => array(0 => array('forum_id' => 0, 'wrw_read_topics' => 0, 'wrw_read_posts' => 0, 'wrw_read_last' => 0)),
			'topic' => array(),
		);

		/* WRW Statistics, both forum and topic */
		foreach (array_keys($wrw) as $type)
		{
			$sql = "SELECT {$type}_id, COUNT(DISTINCT(post_id)) as wrw_read_posts, MAX(read_time) as wrw_read_last" . ($type === 'forum' ? ', COUNT(DISTINCT(topic_id)) as wrw_read_topics' : '') . '
					FROM ' . $this->table . '
					WHERE ' . $this->db->sql_in_set('user_id', $user_ids) . "
					GROUP BY {$type}_id";
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				/* Add this row to the correct type, indexed by the identifier */
				$wrw[$type][(int) $row[$type . '_id']] = $row;

				/* If we're still in forum type, we add the statistics to the board index (overall) statistics */
				if ($type === 'forum')
				{
					$wrw['forum'][0]['wrw_read_topics']	+= $row['wrw_read_topics'];
					$wrw['forum'][0]['wrw_read_posts']	+= $row['wrw_read_posts'];
					$wrw['forum'][0]['wrw_read_last']	= ($row['wrw_read_last'] > $wrw['forum'][0]['wrw_read_last']) ? $row['wrw_read_last'] : $wrw['forum'][0]['wrw_read_last'];
				}
			}
			$this->db->sql_freeresult($result);
		}

		$root_data = array('forum_id' => 0);

		if ($s_forum)
		{
			/* We are inside a forum, let's grab this forum's data */
			$sql = 'SELECT * FROM ' . FORUMS_TABLE . ' WHERE forum_id = ' . (int) $f;
			$result = $this->db->sql_query($sql);
			$root_data = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			/* Set up forum breadcrumbs */
			$this->breadcrumbs($root_data);

			/* Lets do some topics */
			$this->topics($root_data, $wrw['topic'], $user_ids);
		}
		else if ($s_topic)
		{
			/* We are inside a topic, let's grab this topic's data */
			$sql = 'SELECT f.* FROM ' . FORUMS_TABLE . ' f, ' . TOPICS_TABLE . ' t WHERE t.forum_id = f.forum_id AND t.topic_id = ' . (int) $t;
			$result = $this->db->sql_query($sql);
			$root_data = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			/* Set up forum breadcrumbs */
			$this->breadcrumbs($root_data);

			/* Lets do some posts */
			$this->posts($root_data['forum_id'], $t, $user_ids);
		}

		if (!$s_topic)
		{
			/* Let's do some forums */
			$wrw_forums = $this->forums($root_data, $wrw['forum']);

			if (!$s_index)
			{
				$read_f_row = isset($wrw['forum'][$root_data['forum_id']]) ? $wrw['forum'][$root_data['forum_id']] : false;

				$read_f_sum = array(
					'wrw_read_topics'	=> $read_f_row ? $wrw_forums['wrw_read_topics'] + $read_f_row['wrw_read_topics'] : $wrw_forums['wrw_read_topics'],
					'wrw_read_posts'	=> $read_f_row ? $wrw_forums['wrw_read_posts'] + $read_f_row['wrw_read_posts'] : $wrw_forums['wrw_read_posts'],
					'wrw_read_last'		=> (!$read_f_row || ($wrw_forums['wrw_read_last'] > $read_f_row['wrw_read_last'])) ? $wrw_forums['wrw_read_last'] : $read_f_row['wrw_read_last'],
				);
			}
		}

		$read_f_data = $s_index ? $wrw['forum'][0] : (isset($read_f_sum) ? $read_f_sum : false);
		$read_t_data = isset($wrw['topic'][(int) $t]) ? $wrw['topic'][(int) $t] : false;

		$read_topics = $s_topic ? false : ($read_f_data ? $read_f_data['wrw_read_topics'] : 0);
		$read_posts = $s_topic ? ($read_t_data ? $read_t_data['wrw_read_posts'] : 0) : ($read_f_data ? $read_f_data['wrw_read_posts'] : 0);
		$read_last = $s_topic ? ($read_t_data ? $read_t_data['wrw_read_last'] : 0) : ($read_f_data ? $read_f_data['wrw_read_last'] : 0);

		$this->template->assign_vars(array(
			'WRW_AVATAR'		=> $wrw_avatar,
			'WRW_NAME'			=> $wrw_name,
			'WRW_NAME_FULL'		=> $wrw_name_full,
			'WRW_RANK'			=> $wrw_rank,
			'WRW_RANK_IMG'		=> $wrw_rank_img,

			'WRW_READ_POSTS'	=> $read_posts,
			'WRW_READ_TOPICS'	=> $read_topics,
			'WRW_READ_LAST'		=> $read_last ? $this->user->format_date($read_last) : '',

			'S_WRW_MODE'		=> $mode,
			'S_WRW_HLPOSTS'		=> $this->functions->is_hlposts_enabled(),
			'S_WRW_FORUM'		=> $s_forum,
			'S_WRW_TOPIC'		=> $s_topic,

			'U_WRW_BASE'		=> $this->get_route(),
			'U_WRW_SORT'		=> $this->get_route($f, $t),
			'U_WRW_TOPIC'		=> $this->helper->route('phpbbstudio_wrw_read_list', array('mode' => 'topic', 'id' => (int) $t)),

			'U_VIEW_FORUM'		=> append_sid("{$this->root_path}viewforum.{$this->php_ext}", 'f=' . (int) $root_data['forum_id']),
			'U_VIEW_TOPIC'		=> append_sid("{$this->root_path}viewtopic.{$this->php_ext}", 'f=' . (int) $root_data['forum_id'] . '&amp;t=' . (int) $t),
		));

		return $this->helper->render('@phpbbstudio_wrw/wrw_usergroup.html', $this->lang->lang('WRW_CAT') . ' &bull; ' . $wrw_name);
	}

	/**
	 * Display forums.
	 *
	 * @see display_forums()
	 * 		in: \phpbb\includes\functions_display.php
	 *
	 * @param  array	$root_data		Array with the forum data
	 * @param  array	$wrw			Array with WRW forum statistics
	 * @return array					Array with WRW forum statistics including subforums
	 * @access protected
	 */
	protected function forums($root_data, $wrw)
	{
		/* Let's request some forums! */
		$sql_array = array(
			'SELECT'	=> 'f.*',
			'FROM'		=> array(
				FORUMS_TABLE		=> 'f'
			),
			'WHERE'		=> $root_data['forum_id'] ? 'left_id > ' . $root_data['left_id'] . ' AND left_id < ' . $root_data['right_id'] : '',
			'ORDER_BY'	=> 'f.left_id',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		$forum_rows = $valid_categories = $subforums = array();
		$branch_root_id = $root_data['forum_id'];
		$parent_id = $visible_forums = 0;

		while($row = $this->db->sql_fetchrow($result))
		{
			$forum_id = (int) $row['forum_id'];

			/* Category with no forums */
			if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
			{
				continue;
			}

			/* Skip branch */
			if (isset($right_id))
			{
				if ($row['left_id'] < $right_id)
				{
					continue;
				}
				unset($right_id);
			}

			if (!$this->auth->acl_get('f_list', $forum_id))
			{
				/* if the user does not have permissions to list this forum, skip everything until next branch */
				$right_id = $row['right_id'];
				continue;
			}

			$row['forum_posts'] = $this->con_vis->get_count('forum_posts', $row, $forum_id);
			$row['forum_topics'] = $this->con_vis->get_count('forum_topics', $row, $forum_id);

			/* Fill list of categories with forums */
			if (isset($forum_rows[$row['parent_id']]))
			{
				$valid_categories[$row['parent_id']] = true;
			}

			if ($row['parent_id'] == $root_data['forum_id'] || $row['parent_id'] == $branch_root_id)
			{
				if ($row['forum_type'] != FORUM_CAT)
				{
					$forum_ids_moderator[] = (int) $forum_id;
				}

				/* Direct child of current branch */
				$parent_id = $forum_id;
				$forum_rows[$forum_id] = $row;

				if ($row['forum_type'] == FORUM_CAT && $row['parent_id'] == $root_data['forum_id'])
				{
					$branch_root_id = $forum_id;
				}
			}
			else if ($row['forum_type'] != FORUM_CAT)
			{
				$subforums[$parent_id][$forum_id]['display'] = ($row['display_on_index']) ? true : false;
				$subforums[$parent_id][$forum_id]['name'] = $row['forum_name'];
				$subforums[$parent_id][$forum_id]['children'] = array();
				$subforums[$parent_id][$forum_id]['type'] = $row['forum_type'];

				if (isset($subforums[$parent_id][$row['parent_id']]) && !$row['display_on_index'])
				{
					$subforums[$parent_id][$row['parent_id']]['children'][] = $forum_id;
				}

				$forum_rows[$parent_id]['forum_topics'] += $row['forum_topics'];

				/* Do not list redirects in LINK Forums as Posts. */
				if ($row['forum_type'] != FORUM_LINK)
				{
					$forum_rows[$parent_id]['forum_posts'] += $row['forum_posts'];
				}

				/* If this forum has WRW, we say the parent has it enabled too */
				if ($row['forum_wrw_read'])
				{
					$forum_rows[$parent_id]['forum_wrw_read'] = $row['forum_wrw_read'];
				}
			}
		}

		$wrw_read_posts = $wrw_read_topics = $wrw_read_last = 0;
		$last_catless = true;
		foreach ($forum_rows as $row)
		{
			/* Category */
			if ($row['parent_id'] == $root_data['forum_id'] && $row['forum_type'] == FORUM_CAT)
			{
				/* Do not display categories without any forums to display */
				if (!isset($valid_categories[$row['forum_id']]))
				{
					continue;
				}

				$cat_row = array(
					'S_IS_CAT'				=> true,
					'FORUM_ID'				=> $row['forum_id'],
					'FORUM_NAME'			=> $row['forum_name'],
					'FORUM_DESC'			=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
					'U_VIEWFORUM'			=> $this->get_route($row['forum_id']),
				);

				$this->template->assign_block_vars('forumrow', $cat_row);

				continue;
			}

			$visible_forums++;
			$forum_id = $row['forum_id'];

			if (isset($wrw[$forum_id]))
			{
				$wrw_forum = $wrw[$forum_id];

				$wrw_read_posts += $wrw_forum['wrw_read_posts'];
				$wrw_read_topics += $wrw_forum['wrw_read_topics'];

				if ($wrw_forum['wrw_read_last'] > $wrw_read_last)
				{
					$wrw_read_last = $wrw_forum['wrw_read_last'];
				}
			}

			$l_subforums = '';
			$subforums_list = array();

			/* Generate list of subforums if we need to */
			if (isset($subforums[$forum_id]))
			{
				foreach ($subforums[$forum_id] as $subforum_id => $subforum_row)
				{
					if (isset($wrw[$subforum_id]))
					{
						$wrw_forum = $wrw[$subforum_id];

						$wrw_read_posts += $wrw_forum['wrw_read_posts'];
						$wrw_read_topics += $wrw_forum['wrw_read_topics'];

						if ($wrw_forum['wrw_read_last'] > $wrw_read_last)
						{
							$wrw_read_last = $wrw_forum['wrw_read_last'];
						}
					}

					if ($subforum_row['display'] && $subforum_row['name'])
					{
						$subforums_list[] = array(
							'link'		=> $this->get_route($subforum_id),
							'name'		=> $subforum_row['name'],
							'type'		=> $subforum_row['type'],
						);
					}
					else
					{
						unset($subforums[$forum_id][$subforum_id]);
					}
				}

				$l_subforums = (count($subforums[$forum_id]) == 1) ? $this->lang->lang('SUBFORUM') : $this->lang->lang('SUBFORUMS');
			}

			$subforums_row = array();
			foreach ($subforums_list as $subforum)
			{
				$subforums_row[] = array(
					'U_SUBFORUM'	=> $subforum['link'],
					'SUBFORUM_NAME'	=> $subforum['name'],
					'IS_LINK'		=> $subforum['type'] == FORUM_LINK,
				);
			}
			$catless = ($row['parent_id'] == $root_data['forum_id']) ? true : false;

			$forum_row = array(
				'S_IS_CAT'				=> false,
				'S_NO_CAT'				=> $catless && !$last_catless,
				'S_IS_LINK'				=> ($row['forum_type'] == FORUM_LINK) ? true : false,
				'S_LIST_SUBFORUMS'		=> ($row['display_subforum_list']) ? true : false,
				'S_SUBFORUMS'			=> (count($subforums_list)) ? true : false,

				'FORUM_ID'				=> $row['forum_id'],
				'FORUM_NAME'			=> $row['forum_name'],
				'FORUM_DESC'			=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
				'POSTS'					=> $row['forum_posts'],
				'TOPICS'				=> $row['forum_topics'],

				'WRW_POSTS'				=> isset($wrw[$row['forum_id']]['wrw_read_posts']) ? $wrw[$row['forum_id']]['wrw_read_posts'] : 0,
				'WRW_PERCENT'			=> (isset($wrw[$row['forum_id']]['wrw_read_posts']) && $row['forum_posts']) ? round((($wrw[$row['forum_id']]['wrw_read_posts'] / $row['forum_posts']) * 100), 2) : 0,
				'WRW_TIME'				=> isset($wrw[$row['forum_id']]['wrw_read_last']) ? $this->user->format_date($wrw[$row['forum_id']]['wrw_read_last']) : '',

				'S_WRW_ENABLED'			=> $row['forum_wrw_read'],

				'L_SUBFORUM_STR'		=> $l_subforums,

				'U_VIEWFORUM'			=> $this->get_route($row['forum_id']),
			);

			$this->template->assign_block_vars('forumrow', $forum_row);

			/* Assign subforums loop for style authors */
			$this->template->assign_block_vars_array('forumrow.subforum', $subforums_row);

			$last_catless = $catless;
		}

		$this->template->assign_vars(array(
			'FORUM_NAME'	=> isset($root_data['forum_name']) ? $root_data['forum_name'] : $this->lang->lang('FORUM_INDEX'),
		));

		return array(
			'wrw_read_topics'	=> $wrw_read_topics,
			'wrw_read_posts'	=> $wrw_read_posts,
			'wrw_read_last'		=> $wrw_read_last,
		);
	}

	/**
	 * Display topics.
	 *
	 * @see \phpbb\viewforum.php
	 *
	 * @param  array	$forum_data		Array with the forum data
	 * @param  array	$wrw			Array with the topic statistics
	 * @param  array	$user_ids		Array with the user identifiers
	 * @return void
	 * @access protected
	 */
	protected function topics($forum_data, $wrw, $user_ids)
	{
		$this->lang->add_lang('viewforum');

		$s_hlposts = $this->functions->is_hlposts_enabled();

		$default_sort_days	= (!empty($this->user->data['user_topic_show_days'])) ? $this->user->data['user_topic_show_days'] : 0;
		$default_sort_key	= (!empty($this->user->data['user_topic_sortby_type'])) ? $this->user->data['user_topic_sortby_type'] : 't';
		$default_sort_dir	= (!empty($this->user->data['user_topic_sortby_dir'])) ? $this->user->data['user_topic_sortby_dir'] : 'd';

		$sort_days	= $this->request->variable('st', $default_sort_days);
		$sort_key	= $this->request->variable('sk', $default_sort_key);
		$sort_dir	= $this->request->variable('sd', $default_sort_dir);

		$start = $this->request->variable('start', 0);
		$forum_id = $forum_data['forum_id'];

		/* Permissions check */
		if (!$this->auth->acl_gets('f_list', 'f_list_topics', 'f_read', $forum_id))
			if (false)
			{
				if ($this->user->data['user_id'] != ANONYMOUS)
				{
					send_status_line(403, 'Forbidden');
					trigger_error('SORRY_AUTH_READ');
				}
				login_box('', $this->lang->lang('LOGIN_VIEWFORUM'));
			}

		/**
		 * Forum is passworded ... check whether access has been granted to this
		 * user this session, if not show login box
		 */
		if ($forum_data['forum_password'])
		{
			login_forum_box($forum_data);
		}

		/* Is a forum specific topic count required? */
		if ($forum_data['forum_topics_per_page'])
		{
			$this->config['topics_per_page'] = $forum_data['forum_topics_per_page'];
		}

		$topics_count = $this->con_vis->get_count('forum_topics', $forum_data, $forum_id);
		$start = $this->pagination->validate_start($start, $this->config['topics_per_page'], $topics_count);

		$limit_days = array(0 => $this->lang->lang('ALL_TOPICS'), 1 => $this->lang->lang('1_DAY'), 7 => $this->lang->lang('7_DAYS'), 14 => $this->lang->lang('2_WEEKS'), 30 => $this->lang->lang('1_MONTH'), 90 => $this->lang->lang('3_MONTHS'), 180 => $this->lang->lang('6_MONTHS'), 365 => $this->lang->lang('1_YEAR'));

		$l_auto = $this->lang->lang('WRW_AUTO') . ' ';
		$l_man	= $this->lang->lang('WRW_MAN') . ' ';

		$sort_by_text = array(
			'a'  => $this->lang->lang('AUTHOR'),
			't'  => $this->lang->lang('POST_TIME'),
			'r'  => $this->lang->lang('REPLIES'),
			's'  => $this->lang->lang('SUBJECT'),
			'rr' => ($s_hlposts ? $l_auto : '') . $this->lang->lang('WRW_READ_POSTS'),
			'rp' => ($s_hlposts ? $l_auto : '') . $this->lang->lang('WRW_READ_PERCENTAGE'),
			'rt' => ($s_hlposts ? $l_auto : '') . $this->lang->lang('WRW_READ_TIME'),
		);
		$sort_by_sql = array(
			'a'  => 't.topic_first_poster_name',
			't'  => array('t.topic_last_post_time', 't.topic_last_post_id'),
			'r'  => (($this->auth->acl_get('m_approve', $forum_id)) ? 't.topic_posts_approved + t.topic_posts_unapproved + t.topic_posts_softdeleted' : 't.topic_posts_approved'),
			's'  => 'LOWER(t.topic_title)',
			'rr' => array('COUNT(wrw.post_id)', 't.topic_last_post_time', 't.topic_last_post_id'),
			'rp' => array('(COUNT(wrw.post_id) / t.topic_posts_approved)', 't.topic_last_post_time', 't.topic_last_post_id'),
			'rt' => 'MAX(wrw.read_time)',
		);

		if ($s_hlposts)
		{
			$sort_by_text['hr'] = $l_man . $this->lang->lang('WRW_READ_POSTS');
			$sort_by_text['hp'] = $l_man . $this->lang->lang('WRW_READ_PERCENTAGE');
			$sort_by_text['ht'] = $l_man . $this->lang->lang('WRW_READ_TIME');

			$sort_by_sql['hr'] = array('COUNT(hp.post_id)', 't.topic_last_post_time', 't.topic_last_post_id');
			$sort_by_sql['hp'] = array('(COUNT(hp.post_id) / t.topic_posts_approved)', 't.topic_last_post_time', 't.topic_last_post_id');
			$sort_by_sql['ht'] = 'MAX(hp.read_time)';
		}

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param, $default_sort_days, $default_sort_key, $default_sort_dir);

		if ($sort_days)
		{
			$min_post_time = time() - ($sort_days * 86400);

			$sql_array = array(
				'SELECT'	=> 'COUNT(t.topic_id) AS num_topics',
				'FROM'		=> array(
					TOPICS_TABLE	=> 't',
				),
				'WHERE'		=> 't.forum_id = ' . $forum_id . '
					AND t.topic_last_post_time >= ' . $min_post_time . '
					AND ' . $this->con_vis->get_visibility_sql('topic', $forum_id, 't.'),
			);

			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql_array));
			$topics_count = (int) $this->db->sql_fetchfield('num_topics');
			$this->db->sql_freeresult($result);

			if (isset($_POST['sort']))
			{
				$start = 0;
			}

			$sql_limit_time = "AND t.topic_last_post_time >= $min_post_time";

			/* Make sure we have information about day selection ready */
			$this->template->assign_var('S_SORT_DAYS', true);
		}
		else
		{
			$sql_limit_time = '';
		}

		$sql_approved = ' AND ' . $this->con_vis->get_visibility_sql('topic', $forum_id, 't.');

		$store_reverse = false;
		$sql_limit = $this->config['topics_per_page'];
		if ($start > $topics_count / 2)
		{
			$store_reverse = true;

			/* Select the sort order */
			$direction = (($sort_dir == 'd') ? 'ASC' : 'DESC');

			$sql_limit = $this->pagination->reverse_limit($start, $sql_limit, $topics_count);
			$sql_start = $this->pagination->reverse_start($start, $sql_limit, $topics_count);
		}
		else
		{
			/* Select the sort order */
			$direction = (($sort_dir == 'd') ? 'DESC' : 'ASC');

			$sql_start = $start;
		}

		if (is_array($sort_by_sql[$sort_key]))
		{
			$sql_sort_order = implode(' ' . $direction . ', ', $sort_by_sql[$sort_key]) . ' ' . $direction;
		}
		else
		{
			$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . $direction;
		}

		$sql_where = 't.forum_id = ' . (int) $forum_id;

		$topic_list = $rowset = $sql_left_join = $hlposts = array();

		$sql_left_join[] = array(
			'FROM'	=> array($this->table => 'wrw'),
			'ON'	=> 't.topic_id = wrw.topic_id AND ' . $this->db->sql_in_set('wrw.user_id', $user_ids),
		);

		if ($s_hlposts)
		{
			$sql_left_join[] = array(
				'FROM'	=> array(POSTS_TABLE => 'hpp'),
				'ON'	=> 't.topic_id = hpp.topic_id'
			);
			$sql_left_join[] = array(
				'FROM'	=> array($this->table_prefix . 'post_read' => 'hp'),
				'ON'	=> 'hp.post_id = hpp.post_id AND ' . $this->db->sql_in_set('hp.user_id', $user_ids),
			);
		}

		$sql_ary = array(
			'SELECT'	=> 't.topic_id',
			'FROM'		=> array(
				TOPICS_TABLE => 't',
			),
			'LEFT_JOIN'	=> $sql_left_join,
			'WHERE'		=> "$sql_where
				AND t.topic_type IN (" . POST_NORMAL . ', ' . POST_STICKY . ")
				$sql_approved
				$sql_limit_time",
			'GROUP_BY'	=> 't.topic_id',
			'ORDER_BY'	=> 't.topic_type ' . ((!$store_reverse) ? 'DESC' : 'ASC') . ', ' . $sql_sort_order,
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query_limit($sql, $sql_limit, $sql_start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topic_list[] = (int) $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($topic_list))
		{
			if ($s_hlposts)
			{
				$sql = 'SELECT p.topic_id, COUNT(hp.post_id) as hp_read_posts, MAX(hp.read_time) as hp_read_last
						FROM ' . $this->table_prefix . 'post_read hp,
							' . POSTS_TABLE . ' p
						WHERE hp.post_id = p.post_id
							AND ' . $this->db->sql_in_set('hp.user_id', $user_ids) . '
							AND ' . $this->db->sql_in_set('p.topic_id' , $topic_list) . '
						GROUP BY p.topic_id';
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$hlposts[$row['topic_id']] = $row;
				}
				$this->db->sql_freeresult($result);
			}

			/* SQL array for obtaining topics/stickies */
			$sql_array = array(
				'SELECT'		=> 't.*,
									u1.user_avatar as author_avatar, u1.user_avatar_type as author_avatar_type, u1.user_avatar_width as author_avatar_width, u1.user_avatar_height as author_avatar_height,
									u2.user_avatar as poster_avatar, u2.user_avatar_type as poster_avatar_type, u2.user_avatar_width as poster_avatar_width, u2.user_avatar_height as poster_avatar_height',
				'FROM'			=> array(TOPICS_TABLE	=> 't'),
				'LEFT_JOIN'		=> array(
					array(
						'FROM'		=> array(USERS_TABLE => 'u1'),
						'ON'		=> 't.topic_poster = u1.user_id',
					),
					array(
						'FROM'		=> array(USERS_TABLE => 'u2'),
						'ON'		=> 't.topic_last_poster_id = u2.user_id',
					),
				),
				'WHERE'			=> $this->db->sql_in_set('t.topic_id', $topic_list),
			);

			/**
			 * If store_reverse, then first obtain topics, then stickies, else the other way around...
			 * Funnily enough you typically save one query if going from the last page to the middle (store_reverse) because
			 * the number of stickies are not known
			 */
			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$rowset[$row['topic_id']] = $row;
			}
			$this->db->sql_freeresult($result);
		}

		$base_url = $this->get_route($forum_id) . (strlen($u_sort_param) ? "&amp;$u_sort_param" : '');
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $topics_count, $this->config['topics_per_page'], $start);

		$this->template->assign_vars(array(
			'TOTAL_TOPICS'	=> $this->lang->lang('VIEW_FORUM_TOPICS', (int) $topics_count),
		));

		if (count($topic_list))
		{
			foreach ($topic_list as $topic_id)
			{
				$row = &$rowset[$topic_id];
				$topic_forum_id = ($row['forum_id']) ? (int) $row['forum_id'] : $forum_id;

				$wrw_row = isset($wrw[(int) $topic_id]) ? $wrw[(int) $topic_id] : false;
				$hlp_row = isset($hlposts[(int) $topic_id]) ? $hlposts[(int) $topic_id] : false;

				/* Avatars */
				$ava_row = array('author' => array(), 'poster' => array());
				foreach (array_keys($ava_row) as $type)
				{
					$ava_row[$type] = array(
						'user_avatar'			=> $row[$type . '_avatar'],
						'user_avatar_type'		=> $row[$type . '_avatar_type'],
						'user_avatar_width'		=> $row[$type . '_avatar_width'],
						'user_avatar_height'	=> $row[$type . '_avatar_height'],
					);
				}

				/* Replies */
				$posts = $this->con_vis->get_count('topic_posts', $row, $topic_forum_id);
				$replies = $posts - 1;

				/**
				 * Default avatar
				 */
				$no_avatar = $this->functions->wrw_no_avatar();

				/**
				 * DAE (Default Avatar Extended) extension compatibility
				 */
				if ( $this->functions->is_dae_enabled() )
				{
					$user_av_author = phpbb_get_user_avatar($ava_row['author'], '');
					$user_av_poster = phpbb_get_user_avatar($ava_row['poster'], '');
				}
				else
				{
					$user_av_author = (!empty($ava_row['author']['user_avatar'])) ? phpbb_get_user_avatar($ava_row['author'], '') : ( $no_avatar ? $no_avatar : '' );
					$user_av_poster = (!empty($ava_row['poster']['user_avatar'])) ? phpbb_get_user_avatar($ava_row['poster'], '') : ( $no_avatar ? $no_avatar : '' );
				}

				$this->template->assign_block_vars('topicrow', array(
					'TOPIC_ID'				=> $topic_id,
					'TOPIC_TITLE'			=> censor_text($row['topic_title']),
					'TOPIC_AUTHOR'			=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
					'TOPIC_AVATAR'			=> $user_av_author,
					'TOPIC_TIME'			=> $this->user->format_date($row['topic_time']),
					'LAST_POST_SUBJECT'		=> censor_text($row['topic_last_post_subject']),
					'LAST_POST_TIME'		=> $this->user->format_date($row['topic_last_post_time']),
					'LAST_POST_AUTHOR'		=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
					'LAST_POST_AVATAR'		=> $user_av_poster,

					'REPLIES'				=> $replies,

					'TOPIC_ICON'			=> !empty($row['topic_type']) ? $this->icons[$row['topic_type']] : (!empty($row['topic_status']) ? $this->icons[POST_NORMAL][$row['topic_status']] : ($row['poll_start'] ? 'fa-bar-chart' : 'file-text')),

					'WRW_POSTS'				=> $wrw_row ? $wrw_row['wrw_read_posts'] : 0,
					'WRW_PERCENT'			=> $wrw_row ? round(($wrw_row['wrw_read_posts'] / ($replies + 1) * 100), 2) : 0,
					'WRW_TIME'				=> $wrw_row ? $this->user->format_date($wrw_row['wrw_read_last']) : '',

					'HLP_POSTS'				=> $hlp_row ? $hlp_row['hp_read_posts'] : 0,
					'HLP_PERCENT'			=> $hlp_row ? round(($hlp_row['hp_read_posts'] / ($replies + 1) * 100), 2) : 0,
					'HLP_TIME'				=> $hlp_row ? $this->user->format_date($hlp_row['hp_read_last']) : '',

					'S_HAS_POLL'			=> ($row['poll_start']) ? true : false,
					'S_POST_ANNOUNCE'		=> ($row['topic_type'] == POST_ANNOUNCE) ? true : false,
					'S_POST_GLOBAL'			=> ($row['topic_type'] == POST_GLOBAL) ? true : false,
					'S_POST_STICKY'			=> ($row['topic_type'] == POST_STICKY) ? true : false,
					'S_TOPIC_LOCKED'		=> ($row['topic_status'] == ITEM_LOCKED) ? true : false,
					'S_TOPIC_MOVED'			=> ($row['topic_status'] == ITEM_MOVED) ? true : false,
					'S_TOPIC_TYPE'			=> $row['topic_type'],

					'U_VIEW'				=> $this->get_route((int) $row['forum_id'], (int) $topic_id),
				));
			}
		}

		$this->template->assign_vars(array(
			'S_SELECT_SORT_DIR'		=> $s_sort_dir,
			'S_SELECT_SORT_KEY'		=> $s_sort_key,
			'S_SELECT_SORT_DAYS'	=> $s_limit_days,
		));
	}

	/**
	 * Display posts.
	 *
	 * @see \phpbb\viewtopic.php
	 *
	 * @param  int		$forum_id		The forum identifier
	 * @param  int		$topic_id		The topic identifier
	 * @param  array	$user_ids		Array with the user identifiers
	 * @return void
	 * @access protected
	 */
	protected function posts($forum_id, $topic_id, $user_ids)
	{
		$this->lang->add_lang('viewtopic');

		$s_hlposts = $this->functions->is_hlposts_enabled();

		$start = $this->request->variable('start', 0);

		$default_sort_days	= (!empty($this->user->data['user_post_show_days'])) ? $this->user->data['user_post_show_days'] : 0;
		$default_sort_key	= (!empty($this->user->data['user_post_sortby_type'])) ? $this->user->data['user_post_sortby_type'] : 't';
		$default_sort_dir	= (!empty($this->user->data['user_post_sortby_dir'])) ? $this->user->data['user_post_sortby_dir'] : 'a';

		$sort_days	= $this->request->variable('st', $default_sort_days);
		$sort_key	= $this->request->variable('sk', $default_sort_key);
		$sort_dir	= $this->request->variable('sd', $default_sort_dir);

		$sql = 'SELECT t.*, f.forum_password
			FROM ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f
			WHERE f.forum_id = t.forum_id AND t.topic_id = ' . (int) $topic_id;
		$result = $this->db->sql_query($sql);
		$topic_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$topic_data || !$this->con_vis->is_visible('topic', $forum_id, $topic_data))
		{
			$this->template->assign_vars(array(
				'S_WRW_ERROR'	=> true,
				'WRW_ERROR_MSG'	=> !$topic_data ? $this->lang->lang('NO_TOPIC') : $this->lang->lang('SORRY_AUTH_READ'),
			));

			return;
		}

		$topic_replies = $this->con_vis->get_count('topic_posts', $topic_data, $forum_id) - 1;

		/* Start auth check */
		if (!$this->auth->acl_get('f_read', $forum_id))
		{
			if ($this->user->data['user_id'] != ANONYMOUS)
			{
				$this->template->assign_vars(array(
					'S_WRW_ERROR'	=> true,
					'WRW_ERROR_MSG'	=> $this->lang->lang('SORRY_AUTH_READ'),
				));

				return;
			}

			login_box('', $this->lang->lang('LOGIN_VIEW_FORUM'));
		}

		/**
		 * Forum is passworded ... check whether access has been granted to this
		 * user this session, if not show login box
		 */
		if ($topic_data['forum_password'])
		{
			login_forum_box($topic_data);
		}

		/* Post ordering options */
		$limit_days = array(0 => $this->lang->lang('ALL_POSTS'), 1 => $this->lang->lang('1_DAY'), 7 => $this->lang->lang('7_DAYS'), 14 => $this->lang->lang('2_WEEKS'), 30 => $this->lang->lang('1_MONTH'), 90 => $this->lang->lang('3_MONTHS'), 180 => $this->lang->lang('6_MONTHS'), 365 => $this->lang->lang('1_YEAR'));

		$l_auto = $this->lang->lang('WRW_AUTO') . ' ';
		$l_man	= $this->lang->lang('WRW_MAN') . ' ';

		$sort_by_text = array(
			'a'  => $this->lang->lang('AUTHOR'),
			't'  => $this->lang->lang('POST_TIME'),
			'r'  => $this->lang->lang('REPLIES'),
			's'  => $this->lang->lang('SUBJECT'),
			'rr' => ($s_hlposts ? $l_auto : '') . $this->lang->lang('WRW_READ_POSTS'),
			'rt' => ($s_hlposts ? $l_auto : '') . $this->lang->lang('WRW_READ_TIME'),
		);
		$sort_by_sql = array(
			'a' => array('u.username_clean', 'p.post_id'),
			't' => array('p.post_time', 'p.post_id'),
			's' => array('p.post_subject', 'p.post_id'),
			'rr' => array('wrw.post_id', 'p.post_id'),
			'rt' => array('MAX(wrw.read_time)', 'p.post_id'),
		);

		if ($s_hlposts)
		{
			$sort_by_text['hr'] = $l_man . $this->lang->lang('WRW_READ_POSTS');
			$sort_by_text['ht'] = $l_man . $this->lang->lang('WRW_READ_TIME');

			$sort_by_sql['hr'] = array('hp.post_id', 'p.post_id');
			$sort_by_sql['ht'] = array('MAX(hp.read_time)', 'p.post_id');
		}

		if ($this->mode === 'group')
		{
			$sort_by_text['rc'] = $this->lang->lang('WRW_READ_USERS');
			$sort_by_sql['rc'] = array('COUNT(DISTINCT(wrw.user_id))', 'p.post_id');

			if ($s_hlposts)
			{
				$sort_by_text['hc'] = $l_man . $this->lang->lang('WRW_READ_USERS');
				$sort_by_sql['hc'] = array('COUNT(DISTINCT(hp.user_id))', 'p.post_id');
			}
		}

		$join_user_sql = array('a' => true, 't' => false, 's' => false, 'rr' => false, 'rl' => false, 'rc' => false, 'hr' => false, 'hl' => false, 'hc' => false);

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param, $default_sort_days, $default_sort_key, $default_sort_dir);

		/**
		 * Obtain correct post count and ordering SQL if user has
		 * requested anything different
		 */
		if ($sort_days)
		{
			$min_post_time = time() - ($sort_days * 86400);
			$sql = 'SELECT COUNT(post_id) AS num_posts
					FROM ' . POSTS_TABLE . "
					WHERE topic_id = $topic_id
						AND post_time >= $min_post_time
							AND " . $this->con_vis->get_visibility_sql('post', $forum_id);
			$result = $this->db->sql_query($sql);
			$total_posts = (int) $this->db->sql_fetchfield('num_posts');
			$this->db->sql_freeresult($result);

			$limit_posts_time = "AND p.post_time >= $min_post_time ";

			if (isset($_POST['sort']))
			{
				$start = 0;
			}
		}
		else
		{
			$total_posts = $topic_replies + 1;
			$limit_posts_time = '';
		}

		/* Make sure $start is set to the last page if it exceeds the amount */
		$start = $this->pagination->validate_start($start, $this->config['posts_per_page'], $total_posts);

		/* Replace naughty words in title */
		$topic_data['topic_title'] = censor_text($topic_data['topic_title']);

		$base_url = $this->get_route($forum_id, $topic_id) . (strlen($u_sort_param) ? "&amp;$u_sort_param" : '');
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_posts, $this->config['posts_per_page'], $start);

		/* If the user is trying to reach the second half of the topic, fetch it starting from the end */
		$store_reverse = false;
		$sql_limit = $this->config['posts_per_page'];

		$post_list = $rowset = array();

		if ($start > $total_posts / 2)
		{
			$store_reverse = true;

			/* Select the sort order */
			$direction = (($sort_dir == 'd') ? 'ASC' : 'DESC');

			$sql_limit = $this->pagination->reverse_limit($start, $sql_limit, $total_posts);
			$sql_start = $this->pagination->reverse_start($start, $sql_limit, $total_posts);
		}
		else
		{
			/* Select the sort order */
			$direction = (($sort_dir == 'd') ? 'DESC' : 'ASC');
			$sql_start = $start;
		}
		if (is_array($sort_by_sql[$sort_key]))
		{
			$sql_sort_order = implode(' ' . $direction . ', ', $sort_by_sql[$sort_key]) . ' ' . $direction;
		}
		else
		{
			$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . $direction;
		}

		$sql = 'SELECT p.post_id
				FROM ' . POSTS_TABLE . ' p' . (($join_user_sql[$sort_key]) ? ', ' . USERS_TABLE . ' u': '') . '
				LEFT JOIN ' . $this->table . ' wrw
					ON p.post_id = wrw.post_id AND ' . $this->db->sql_in_set('wrw.user_id', $user_ids) . "
				WHERE p.topic_id = $topic_id
					AND " . $this->con_vis->get_visibility_sql('post', $forum_id, 'p.') . "
					" . (($join_user_sql[$sort_key]) ? 'AND u.user_id = p.poster_id': '') . "
					$limit_posts_time
				GROUP BY p.post_id
				ORDER BY $sql_sort_order";
		$result = $this->db->sql_query_limit($sql, $sql_limit, $sql_start);

		$i = ($store_reverse) ? $sql_limit - 1 : 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$post_list[$i] = (int) $row['post_id'];
			($store_reverse) ? $i-- : $i++;
		}
		$this->db->sql_freeresult($result);

		if (!count($post_list))
		{
			if ($sort_days)
			{
				$this->template->assign_vars(array(
					'S_WRW_ERROR'	=> true,
					'WRW_ERROR_MSG'	=> $this->lang->lang('NO_POSTS_TIME_FRAME'),
				));

				return;
			}
			else
			{
				$this->template->assign_vars(array(
					'S_WRW_ERROR'	=> true,
					'WRW_ERROR_MSG'	=> $this->lang->lang('NO_TOPIC'),
				));

				return;
			}
		}

		$sql_ary = array(
			'SELECT'	=> 'p.post_id, p.post_subject, p.post_text, p.post_time,
							u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height',
			'FROM'		=> array(
				USERS_TABLE		=> 'u',
				POSTS_TABLE		=> 'p',
			),
			'WHERE'		=> $this->db->sql_in_set('p.post_id', $post_list) . ' AND u.user_id = p.poster_id',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[$row['post_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		/* Get WRW and HLPOSTS statistics */
		$sql_array = array(
			'SELECT'	=> 'p.post_id, COUNT(DISTINCT(wrw.user_id)) as wrw_user_count, MAX(wrw.read_time) as wrw_read_last',
			'FROM'		=> array(POSTS_TABLE => 'p'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array($this->table => 'wrw'),
					'ON'	=> 'p.post_id = wrw.post_id AND ' . $this->db->sql_in_set('wrw.user_id', $user_ids),
				),
			),
			'WHERE'		=> $this->db->sql_in_set('p.post_id', $post_list),
			'GROUP_BY'	=> 'p.post_id',
		);


		if ($s_hlposts)
		{
			/**
			* To enable ASSOCIATED metrics (users count) like in the read_controller use:
			*	'ON'	=> 'wrw.user_id = hp.user_id AND p.post_id = hp.post_id AND ' . $this->db->sql_in_set('hp.user_id', $user_ids),
			*/
			$sql_array['SELECT'] .= ', COUNT(DISTINCT(hp.user_id)) as hp_user_count, MAX(hp.read_time) as hp_read_last';
			$sql_array['LEFT_JOIN'][] = array(
				'FROM'	=> array($this->table_prefix . 'post_read' => 'hp'),
				'ON'	=> 'p.post_id = hp.post_id AND ' . $this->db->sql_in_set('hp.user_id', $user_ids),
			);
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[$row['post_id']] = array_merge($row, $rowset[$row['post_id']]);
		}
		$this->db->sql_freeresult($result);

		/* Output the posts */
		for ($i = 0, $end = count($post_list); $i < $end; $i++)
		{
			/**
			 * A non-existing rowset only happens if there was no user present for the entered poster_id
			 * This could be a broken posts table.
			 */
			if (!isset($rowset[$post_list[$i]]))
			{
				continue;
			}

			$row = $rowset[$post_list[$i]];

			if (!function_exists('truncate_string'))
			{
				/** @noinspection PhpIncludeInspection */
				include $this->root_path . 'includes/functions_content.' . $this->php_ext;
			}

			$message = $this->utils->clean_formatting($row['post_text']);
			$message = truncate_string($message, 255, 255, false, $this->lang->lang('ELLIPSIS'));

			/* Map arguments for phpbb_get_avatar() */
			$row_avatar = array(
				'avatar'		 => $row['user_avatar'],
				'avatar_type'	 => $row['user_avatar_type'],
				'avatar_height'	 => $row['user_avatar_width'],
				'avatar_width'	 => $row['user_avatar_height'],
			);

			$no_avatar = $this->functions->wrw_no_avatar();

			/**
			 * DAE (Default Avatar Extended) extension compatibility
			 */
			$user_av = $this->functions->is_dae($row_avatar, $row, $no_avatar);

			$this->template->assign_block_vars('postrow', array(
				'POSTER_NAME'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'POSTER_AVATAR'	=> $user_av,
				'POST_TIME'		=> $this->user->format_date($row['post_time']),

				'SUBJECT'		=> censor_text($row['post_subject']),
				'MESSAGE'		=> $message,

				'WRW_COUNT'		=> (int) $row['wrw_user_count'],
				'WRW_TIME'		=> !empty($row['wrw_read_last']) ? $this->user->format_date($row['wrw_read_last']) : '',
				'S_WRW_READ'	=> !empty($row['wrw_user_count']),

				'HP_COUNT'		=> isset($row['hp_user_count']) ? (int) $row['hp_user_count'] : 0,
				'HP_TIME'		=> isset($row['hp_read_last']) && !empty($row['hp_read_last']) ? $this->user->format_date($row['hp_read_last']) : '',
				'S_HP_READ'		=> isset($row['hp_user_count']) && !empty($row['hp_user_count']),

				'U_POSTER'		=> get_username_string('profile', $row['user_id'], $row['username'], $row['user_colour']),
				'U_WRW_POST'	=> $this->helper->route('phpbbstudio_wrw_read_list', array('mode' => 'post', 'id' => (int) $row['post_id'])),
			));
		}

		$this->template->assign_vars(array(
			'TOPIC_TITLE'	=> $topic_data['topic_title'],
			'TOTAL_POSTS'	=> $this->lang->lang('VIEW_TOPIC_POSTS', (int) $total_posts),

			'POSTS_COUNT'	=> (int) $total_posts,

			'S_SELECT_SORT_DIR'		=> $s_sort_dir,
			'S_SELECT_SORT_KEY'		=> $s_sort_key,
			'S_SELECT_SORT_DAYS'	=> $s_limit_days,

			'U_TOPIC'		=> $this->get_route($forum_id, $topic_id),
		));
	}

	/**
	 * Create forum navigation links for current forum, create parent list if currently null.
	 *
	 * @see generate_forum_nav()
	 * 		in: \phpbb\includes\functions_display.php
	 *
	 * @param  array	$root_data		Array with the forum data
	 * @return void
	 * @access protected
	 */
	protected function breadcrumbs($root_data)
	{
		if (!$this->auth->acl_get('f_list', $root_data['forum_id']))
		{
			return;
		}

		if (!function_exists('get_forum_parents'))
		{
			/** @noinspection PhpIncludeInspection */
			include $this->root_path . 'includes/functions_display.' . $this->php_ext;
		}

		$navlinks_parents = array();
		$forum_parents = get_forum_parents($root_data);

		if (!empty($forum_parents))
		{
			foreach ($forum_parents as $parent_id => $parent_data)
			{
				list($parent_name, $parent_type) = array_values($parent_data);

				/* Skip this parent if the user does not have the permission to view it */
				if (!$this->auth->acl_get('f_list', $parent_id))
				{
					continue;
				}

				$navlinks_parents[] = array(
					'ID'		=> $parent_id,
					'NAME'		=> $parent_name,
					'TYPE'		=> $parent_type,
					'U_VIEW'	=> $this->get_route($parent_id),
				);
			}
		}

		$navlinks = array(
			'ID'		=> $root_data['forum_id'],
			'NAME'		=> $root_data['forum_name'],
			'TYPE'		=> $root_data['forum_type'],
			'U_VIEW'	=> $this->get_route($root_data['forum_id']),
		);

		$this->template->assign_block_vars_array('wrw_crumbs', $navlinks_parents);
		$this->template->assign_block_vars('wrw_crumbs', $navlinks);
	}

	/**
	 * Generate a route to this controller.
	 *
	 * @param  int		$forum_id		The forum identifier
	 * @param  int		$topic_id		The topic identifier
	 * @return string
	 */
	private function get_route($forum_id = 0, $topic_id = 0)
	{
		$params = array(
			'mode'	=> (string) $this->mode,
			'id'	=> (int) $this->id,
		);

		if ($forum_id)
		{
			$params['f'] = (int) $forum_id;
		}

		if ($topic_id)
		{
			$params['t'] = (int) $topic_id;
		}

		return $this->helper->route('phpbbstudio_wrw_read_usergroup', $params);
	}

	/**
	 * Generate a formatted group name string.
	 *
	 * @param  string	$mode			(normal|profile), to include a link to the group's profile
	 * @param  int		$id				The group identifier
	 * @param  string	$name			The group name
	 * @param  string	$colour			The group colour
	 * @return string					Formatted group name string
	 * @access protected
	 */
	protected function get_groupname_string($mode, $id, $name, $colour)
	{
		$url	= append_sid("{$this->root_path}memberlist.$this->php_ext", 'mode=group&amp;g=' . (int) $id);
		$name	= $this->group_helper->get_name($name);
		$colour	= ($colour ? ' style="color: #' . $colour . '"' : '');

		switch ($mode)
		{
			case 'normal':
				return '<span' . $colour . '>' . $name . '</span>';
			break;

			case 'full':
			default:
				return '<a' . $colour . ' href="' . $url . '">' . $name . '</a>';
			break;
		}
	}
}
