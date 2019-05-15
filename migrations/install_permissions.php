<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\migrations;

/**
 * Install permissions.
 */
class install_permissions extends \phpbb\db\migration\migration
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
	 * Add the dice extension permissions to the database.
	 *
	 * @return array 		Array of permissions
	 * @access public
	 */
	public function update_data()
	{
		return array(
			/* Admin Group permissions */
			array('permission.add', array('a_wrw_admin')),		/* Can administer the extension's ACP */
			array('permission.add', array('a_wrw_metrics')),	/* Can see the metrics */

			/* Registered user Group permissions */
			array('permission.add', array('u_wrw_metrics')),	/* Can see the metrics */
			array('permission.add', array('u_wrw_user')),		/* Can use the extension */
		);
	}
}
