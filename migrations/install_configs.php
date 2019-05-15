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
class install_configs extends \phpbb\db\migration\migration
{
	/**
	 * Check if the migration is effectively installed (entirely optional).
	 *
	 * @return bool 		True if this migration is installed, False if this migration is not installed
	 * @access public
	 */
	public function effectively_installed()
	{
		return isset($this->config['wrw_read_int']);
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
	 * Add the Who Read What extension configurations to the database.
	 *
	 * @return array 		Array of configs
	 * @access public
	 */
	public function update_data()
	{
		return array(
			array('config.add', array('wrw_read_int', 3)),					// Interval in seconds for JQuery to run
			array('config.add', array('wrw_read_pct', 60)),					// Post visibility's Percentage
			array('config.add', array('wrw_read_cpw', '8.23')),				// (Chars Per Word) Word's average length - http://www.ravi.io/language-word-lengths
			array('config.add', array('wrw_read_wpm', 275)),				// 275 (Words Per Minute)
			array('config.add', array('wrw_read_seq', true)),				// Sequential reading, whether only one post ast a time can be considered "in view"
			array('config.add', array('wrw_active', 1)),					// EXT active as default
			array('config.add', array('wrw_format_date', 'Y M d, H:i')),	// Standardized date format
		);
	}
}
