<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\migrations;

/**
 * Install permission roles.
 */
class install_roles extends \phpbb\db\migration\migration
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
	 * Add the WRW extension permissions to the database.
	 *
	 * @return array 		Array of permissions
	 * @access public
	 */
	public function update_data()
	{
		$data = array();

		/* Admin Group permissions */
		if ($this->role_exists('ROLE_ADMIN_FULL'))
		{
			$data[] = array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_wrw_admin', 'role'));
			$data[] = array('permission.permission_set', array('ROLE_ADMIN_FULL', 'a_wrw_metrics', 'role'));
		}

		/* Registered user Group permissions */
		if ($this->role_exists('ROLE_USER_STANDARD'))
		{
			$data[] = array('permission.permission_set', array('ROLE_USER_STANDARD', 'u_wrw_metrics', 'role', false));
			$data[] = array('permission.permission_set', array('ROLE_USER_STANDARD', 'u_wrw_user', 'role'));
		}
		return $data;
	}

	/**
	 * Checks whether the given role does exist or not.
	 *
	 * @param  String	$role	the name of the role
	 * @return bool				true if the role exists, false otherwise.
	 */
	private function role_exists($role)
	{
		$sql = 'SELECT role_id
				FROM ' . ACL_ROLES_TABLE . "
				WHERE role_name = '" . $this->db->sql_escape($role) . "'";
		$result = $this->db->sql_query_limit($sql, 1);
		$role_id = $this->db->sql_fetchfield('role_id');
		$this->db->sql_freeresult($result);

		return (bool) $role_id;
	}
}
