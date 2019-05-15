<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\migrations;

/**
 * Install ACP module.
 */
class install_acp_module extends \phpbb\db\migration\migration
{
	/**
	 * Assign migration file dependencies for this migration.
	 *
	 * @return array		Array of migration files
	 * @access public
	 * @static
	 */
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v32x\v327');
	}

	/**
	 * Add the Who Read What extension module to the database.
	 *
	 * @return array 		Array of configs
	 * @access public
	 */
	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_WRW_CAT'
			)),
			array('module.add', array(
				'acp',
				'ACP_WRW_CAT',
				array(
					'module_basename'	=> '\phpbbstudio\wrw\acp\wrw_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
