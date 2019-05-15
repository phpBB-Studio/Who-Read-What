<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\acp;

/**
 * phpBB Studio's Who Read What ACP module.
 */
class wrw_module
{
	public $page_title;

	public $tpl_name;

	public $u_action;

	/**
	 * @param $id
	 * @param $mode
	 * @return void
	 * @access public
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/* Get services */
		$controller	= $phpbb_container->get('phpbbstudio.wrw.controller.admin');

		/* Set template filename */
		$this->tpl_name = 'wrw_settings';

		/* Set page title */
		$this->page_title = 'ACP_WRW_SETTINGS';

		/* Make the $u_action variable available in the admin controller */
		$controller->set_page_url($this->u_action);

		/* Send it off to be handled with */
		$controller->handle();
	}
}
