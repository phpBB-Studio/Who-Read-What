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
 * ACP listener.
 */
class acp_listener implements EventSubscriberInterface
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
			'core.acp_manage_forums_request_data'		=> 'wrw_acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data'	=> 'wrw_acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form'		=> 'wrw_acp_manage_forums_display_form',
		);
	}

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbbstudio\wrw\core\functions_common */
	protected $functions;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\request\request					$request	Request object
	 * @param  \phpbbstudio\wrw\core\functions_common	$functions	Functions to be used by classes
	 * @return void
	 * @access public
	 */
	public function __construct(
		\phpbb\request\request $request,
		\phpbbstudio\wrw\core\functions_common $functions
	)
	{
		$this->request		= $request;
		$this->functions	= $functions;
	}

	/**
	 * (Add/update actions) - Submit form.
	 *
	 * @event  core.acp_manage_forums_request_data
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_acp_manage_forums_request_data($event)
	{
		/* Check permissions */
		if ($this->functions->has_perm_metrics())
		{
			$event->update_subarray('forum_data', 'forum_wrw_read', $this->request->variable('forum_wrw_read', 0));
		}
	}

	/**
	 * New Forums added (default enabled).
	 *
	 * @event  core.acp_manage_forums_initialise_data
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_acp_manage_forums_initialise_data($event)
	{
		/* Check permissions */
		if ( $this->functions->has_perm_metrics() )
		{
			if ($event['action'] == 'add')
			{
				$event->update_subarray('forum_data', $event['forum_data']['forum_wrw_read'], true);
			}
		}
	}

	/**
	 * ACP forums (template data).
	 *
	 * @event  core.acp_manage_forums_display_form
	 * @param  \phpbb\event\data	$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_acp_manage_forums_display_form($event)
	{
		/* Check permissions */
		if ( $this->functions->has_perm_metrics() )
		{
			$event->update_subarray('template_data', 'S_FORUM_WRW_READ', (bool) $event['forum_data']['forum_wrw_read']);
			$event->update_subarray('template_data', 'S_FORUM_WRW_AUTH', true);
		}
	}
}
