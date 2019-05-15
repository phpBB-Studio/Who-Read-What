<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\acp;

/**
 *  phpBB Studio's Who Read What ACP module info.
 */
class wrw_info
{
	/**
	 * @return array
	 * @access public
	 */
	public function module()
	{
		return array(
			'filename'	=> '\phpbbstudio\wrw\acp\wrw_module',
			'title'		=> 'ACP_WRW_CAT',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_WRW_SETTINGS',
					'auth'	=> 'ext_phpbbstudio/wrw && acl_a_wrw_admin',
					'cat'	=> array('ACP_WRW_CAT')
				),
			),
		);
	}
}
