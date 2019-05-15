<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\migrations;

/**
 * Install database tables and columns.
 */
class install_user_schema extends \phpbb\db\migration\migration
{
	/**
	 * Check if the migration is effectively installed (entirely optional).
	 *
	 * @return bool 		True if this migration is installed, False if this migration is not installed
	 * @access public
	 */
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'wrw_read');
	}

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
	 * Add the Who Read What extension schema to the database.
	 *
	 * @return array 		Array of table schema
	 * @access public
	 */
	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'forums'		=> array(
					'forum_wrw_read'			=>	array('BOOL', 1),
				),
			),
			'add_tables'	=> array(
				$this->table_prefix . 'wrw_read'	=> array(
					'COLUMNS'	=> array(
						'forum_id'			=> array('ULINT', 0),
						'topic_id'			=> array('ULINT', 0),
						'post_id'			=> array('ULINT', 0),
						'user_id'			=> array('ULINT', 0),
						'read_time'			=> array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> array('post_id', 'user_id'),
				),
			),
		);
	}

	/**
	 * Drop the Who Read What schema from the database.
	 *
	 * @return array		Array of table schema
	 * @access public
	 */
	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'forums'	=> array(
					'forum_wrw_read',
				),
			),
			'drop_tables'	=> array(
				$this->table_prefix . 'wrw_read',
			),
		);
	}
}
