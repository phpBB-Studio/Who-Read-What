<?php
/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace phpbbstudio\wrw\migrations;

/**
 * Install configuration.
 */
class update_configs extends \phpbb\db\migration\migration
{
	/**
	 * Check if the migration is effectively installed (entirely optional).
	 *
	 * @return bool 		True if this migration is installed, False if this migration is not installed
	 * @access public
	 */
	public function effectively_installed()
	{
		return $this->config->offsetExists('wrw_read_quote');
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
		return array('\phpbbstudio\wrw\migrations\install_configs');
	}

	/**
	 * Add the Who Read What extension configurations to the database.
	 *
	 * @return array 		Array of configs
	 * @access public
	 */
	public function update_data()
	{
		return array(
			array('config.add', array('wrw_read_quote', 0)), // Include quotes
		);
	}
}
