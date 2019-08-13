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
 * Set up listener.
 */
class setup_listener implements EventSubscriberInterface
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
			'core.user_setup_after'		=> 'setup_lang',
			'core.permissions'			=> 'setup_permissions',
		);
	}

	/** @var \phpbb\language\language */
	protected $lang;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\language\language		$lang	Language object
	 * @return void
	 * @access public
	 */
	public function __construct(\phpbb\language\language $lang)
	{
		$this->lang = $lang;
	}

	/**
	 * Load extension language file during user set up.
	 *
	 * @event  core.user_setup_after
	 * @return void
	 * @access public
	 */
	public function setup_lang()
	{
		$this->lang->add_lang('wrw_common', 'phpbbstudio/wrw');
	}

	/**
	 * Add permissions for WRW - Permission's language file is automatically loaded.
	 *
	 * @event  core.permissions
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function setup_permissions($event)
	{
		$categories = $event['categories'];
		$permissions = $event['permissions'];

		if (empty($categories['phpbb_studio']))
		{
			/* Setting up a custom CAT */
			$categories['phpbb_studio'] = 'ACL_CAT_PHPBB_STUDIO';

			$event['categories'] = $categories;
		}

		$perms = [
			'a_wrw_admin',
			'a_wrw_metrics',
			'u_wrw_metrics',
			'u_wrw_user',
			'u_wrw_check',
		];

		foreach ($perms as $permission)
		{
			$permissions[$permission] = ['lang' => 'ACL_' . utf8_strtoupper($permission), 'cat' => 'phpbb_studio'];
		}

		$event['permissions'] = $permissions;
	}
}
