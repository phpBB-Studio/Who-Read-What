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
 * Read listener.
 */
class read_listener implements EventSubscriberInterface
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
			'core.viewtopic_assign_template_vars_before'	=> 'wrw_read_data',
			'core.viewtopic_get_post_data'					=> 'wrw_read_sql',
			'core.viewtopic_post_rowset_data'				=> 'wrw_read_modify',
			'core.viewtopic_modify_post_row'				=> 'wrw_read_assign',
		);
	}

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

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
	 * @param  \phpbb\template\template					$template	Template object
	 * @param  \phpbb\user								$user		User object
	 * @param  string									$table		Who Read What table
	 * @param  \phpbbstudio\wrw\core\functions_common	$functions	Who Read What common functions
	 * @return void
	 * @access public
	 */
	public function	__construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\user $user,
		$table,
		\phpbbstudio\wrw\core\functions_common $functions
	)
	{
		$this->db			= $db;
		$this->config		= $config;
		$this->helper		= $helper;
		$this->template		= $template;
		$this->user			= $user;
		$this->table		= $table;
		$this->functions	= $functions;
	}

	/**
	 * Assign Who Read What data to the template.
	 *
	 * @event  core.viewtopic_assign_template_vars_before
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_read_data($event)
	{
		/**
		 * Check if we got something to do here else return
		 */
		if (!$this->config['wrw_active'] || !$event['topic_data']['forum_wrw_read'])
		{
			return;
		}

		/**
		 * Check permissions prior to run the code
		 */
		if ($this->functions->is_authed())
		{
			$this->template->assign_vars(array(
				'WRW_READ_INT'		=> (int) $this->config['wrw_read_int'],
				'WRW_READ_PCT'		=> (int) $this->config['wrw_read_pct'],
				'WRW_READ_CPW'		=> (double) $this->config['wrw_read_cpw'],
				'WRW_READ_WPM'		=> (int) $this->config['wrw_read_wpm'],
				'WRW_READ_SEQ'		=> (bool) $this->config['wrw_read_seq'],
				'WRW_READ_QUOTE'	=> $this->config['wrw_read_quote'] ? 'true' : 'false',

				'S_WRW_READ'	=> true,
				'S_WRW_VIEW'	=> (bool) $this->functions->has_perm_metrics(),
				'S_WRW_CHECK'	=> (bool) $this->functions->has_perm_check(),

				'U_WRW_READ'	=> $this->helper->route('phpbbstudio_wrw_read'),
				'U_WRW_LIST'	=> $this->helper->route('phpbbstudio_wrw_read_list', array('mode' => 'topic', 'id' => (int) $event['topic_id'])),
			));
		}
	}

	/**
	 * Add the Who Read What SQL query.
	 *
	 * @event  core.viewtopic_get_post_data
	 * @param  \phpbb\event\data	$event		The event data
	 * @return void
	 * @access public
	 */
	public function wrw_read_sql($event)
	{
		/**
		 * Check if we got something to do here else return
		 */
		if (!$this->config['wrw_active'] || !$event['topic_data']['forum_wrw_read'])
		{
			return;
		}

		/**
		 * Check permissions prior to run the code
		 */
		if ($this->functions->is_authed())
		{
			$sql_ary = $event['sql_ary'];

			$sql_ary['SELECT'] .= ', wrw.read_time as wrw_read_time';

			$sql_ary['LEFT_JOIN'][] = array(
				'FROM'	=> array($this->table => 'wrw'),
				'ON'	=> 'p.post_id = wrw.post_id AND wrw.user_id = ' . (int) $this->user->data['user_id'],
			);

			$event['sql_ary'] = $sql_ary;
		}
	}

	/**
	 * Add Who Read What data from the database row to the post row.
	 *
	 * @event  core.viewtopic_post_rowset_data
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_read_modify($event)
	{
		/**
		 * Check if we got something to do here else return
		 */
		if (!$this->config['wrw_active'] || !$this->functions->forum_enabled($event['rowset_data']['forum_id']))
		{
			return;
		}

		/**
		 * Check permissions prior to run the code
		 */
		if ($this->functions->is_authed())
		{
			$data = $event['rowset_data'];
			$row = $event['row'];

			$data['s_wrw_read'] = !empty($row['wrw_read_time']);
			$data['wrw_read_time'] = isset($row['wrw_read_time']) ? $row['wrw_read_time'] : 0;

			$event['rowset_data'] = $data;
		}
	}

	/**
	 * Assign Who Read What data from the post row to the template row.
	 *
	 * @event  core.viewtopic_modify_post_row
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_read_assign($event)
	{
		/**
		 * Check if we got something to do here else return
		 */
		if (!$this->config['wrw_active'] || !$event['topic_data']['forum_wrw_read'])
		{
			return;
		}

		/**
		 * Check permissions prior to run the code
		 */
		if ($this->functions->is_authed())
		{
			$post = $event['post_row'];
			$row = $event['row'];

			$post['S_WRW_READ'] = (bool) $row['s_wrw_read'];
			$post['WRW_READ_TIME'] = $this->user->format_date($row['wrw_read_time']);

			$event['post_row'] = $post;
		}
	}
}
