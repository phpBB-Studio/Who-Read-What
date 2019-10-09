<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\controller;

/**
 * Class admin_controller
 */
class admin_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * Constructor
	 *
	 * @param  \phpbb\config\config			$config			Config object
	 * @param  \phpbb\language\language		$lang			Language object
	 * @param  \phpbb\log\log				$log			Log object
	 * @param  \phpbb\request\request		$request		Request object
	 * @param  \phpbb\template\template		$template		Template object
	 * @param  \phpbb\user					$user			User object
	 * @return void
	 * @access public
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\language\language $lang,
		\phpbb\log\log $log,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user
	)
	{
		$this->config	= $config;
		$this->lang		= $lang;
		$this->log		= $log;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;
	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function handle()
	{
		$submit = $this->request->is_set_post('submit');

		add_form_key('wrw_settings');

		$read_active	= $this->request->variable('wrw_active', (bool) $this->config['wrw_active']);
		$read_int		= $this->request->variable('wrw_read_int', (int) $this->config['wrw_read_int']);
		$read_cpw		= $this->request->variable('wrw_read_cpw', (double) $this->config['wrw_read_cpw']);
		$read_pct		= $this->request->variable('wrw_read_pct', (int) $this->config['wrw_read_pct']);
		$read_wpm		= $this->request->variable('wrw_read_wpm', (int) $this->config['wrw_read_wpm']);
		$read_seq		= $this->request->variable('wrw_read_seq', (bool) $this->config['wrw_read_seq']);
		$read_quote		= $this->request->variable('wrw_read_quote', (bool) $this->config['wrw_read_quote']);

		if ($submit)
		{
			if (check_form_key('wrw_settings'))
			{
				trigger_error($this->lang->lang('FORM_INVALID'), E_USER_NOTICE);
			}

			/* Update settings */
			$this->config->set('wrw_active', (bool) $read_active);
			$this->config->set('wrw_read_int', (int) $read_int);
			$this->config->set('wrw_read_cpw', (double) $read_cpw);
			$this->config->set('wrw_read_pct', (int) $read_pct);
			$this->config->set('wrw_read_wpm', (int) $read_wpm);
			$this->config->set('wrw_read_seq', (bool) $read_seq);
			$this->config->set('wrw_read_quote', (bool) $read_quote);

			/* Log it */
			$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_ACP_WRW_SETTINGS');

			/* Show success message */
			trigger_error($this->lang->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
		}

		$this->template->assign_vars(array(
			'WRW_READ_ACTIVE'	=> (bool) $read_active,
			'WRW_READ_INT'		=> (int) $read_int,
			'WRW_READ_CPW'		=> (double) $read_cpw,
			'WRW_READ_PCT'		=> (int) $read_pct,
			'WRW_READ_WPM'		=> (int) $read_wpm,
			'WRW_READ_SEQ'		=> (bool) $read_seq,
			'WRW_READ_QUOTE'	=> (bool) $read_quote,

			'U_ACTION'	=> $this->u_action,
		));
	}

	/**
	 * Set custom form action.
	 *
	 * @param  string	$u_action	Custom form action
	 * @return void
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
