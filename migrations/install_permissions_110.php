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
class install_permissions_110 extends \phpbb\db\migration\migration
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
		return array(
			'\phpbb\db\migration\data\v32x\v327',
			'\phpbbstudio\wrw\migrations\install_permissions',
		);
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
			/* Registered user Group permissions */
			array('permission.add', array('u_wrw_check')),	/* Can see the check square indicator */
		);
	}
}
