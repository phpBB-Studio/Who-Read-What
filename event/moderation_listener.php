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
 * Moderation listener.
 */
class moderation_listener implements EventSubscriberInterface
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
			'core.delete_user_after'							=> 'wrw_delete_user_after',			// On deleting an user in ACP.
			'core.delete_post_after'							=> 'wrw_delete_post_after',			// On hitting the X button of a post in view topic.
			//'core.move_posts_after'								=> 'wrw_move_posts_after',			// On after a post has been moved
			'core.delete_topics_before_query'					=> 'wrw_add_wrw_table',				// On delete a topic MCP/Quicktools
			'core.move_topics_before_query'						=> 'wrw_add_wrw_table',				// On move a topic MCP/Quicktools moves the read marks too
			'core.delete_forum_content_before_query'			=> 'wrw_add_wrw_table',				// On delete a forum in ACP
			'core.delete_posts_in_transaction_before'			=> 'wrw_add_wrw_table',				// On delete posts in MCP
			'core.acp_manage_forums_move_content_sql_before'	=> 'wrw_add_wrw_table',				// On move content of a forum in ACP
			//'core.mcp_main_fork_sql_after'						=> 'wrw_mcp_main_fork_sql_after',	//
		);
	}

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string Who Read What table */
	protected $table;

	/**
	 * Constructor.
	 *
	 * @param  \phpbb\db\driver\driver_interface	$db			Database object
	 * @param  \phpbb\request\request				$request	Request object
	 * @param  string								$table		Who Read What table
	 * @return void
	 * @access public
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, $table)
	{
		$this->db			= $db;
		$this->request		= $request;
		$this->table		= $table;
	}

	/**
	 * Performing actions directly after a user has been deleted.
	 *
	 * @event  core.delete_post_after
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_delete_user_after($event)
	{
		$user_ids = $event['user_ids'];

		if (count($user_ids))
		{
			/**
			* In any case our queries are against ANONYMOUS and there is not the need
			* to have a plethora of unused marks with an unique user ID. Let's delete.
			*/
			if ($event['mode'] === 'remove' || $event['mode'] === 'retain')
			{
				$sql = 'DELETE FROM ' . $this->table . '
					WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
				$this->db->sql_query($sql);
			}
		}
	}

	/**
	 * Performing actions directly after a post or topic has been deleted.
	 * On hitting the X button of a post in view topic.
	 *
	 * @event  core.delete_post_after
	 *
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_delete_post_after($event)
	{
		$sql = 'DELETE FROM ' . $this->table . '
				WHERE post_id = ' . (int) $event['post_id'];
		$this->db->sql_query($sql);
	}

	/**
	 * Perform actions after the posts have been moved.
	 *
	 * @event  core.move_posts_after
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_move_posts_after($event)	// Should I implement this one?
	{
/*
		$topic_id = $event['topic_id'];
		$post_ids = $event['post_ids'];
		$forum_row = $event['forum_row'];

		$sql = 'UPDATE ' . $this->table . '
				SET forum_id = ' . (int) $forum_row['forum_id'] . ", topic_id = " . (int) $topic_id . "
				WHERE " . $this->db->sql_in_set('post_id', $post_ids);
		$this->db->sql_query($sql);
*/
	}

	/**
	 * Shared function which adds our WRW table to the native array of tables.
	 *
	 * @event  core.delete_topics_before_query					On delete a topic MCP/Quicktools
	 * @event  core.move_topics_before_query					On move a topic MCP/Quicktools moves the read marks too
	 * @event  core.delete_forum_content_before_query			On delete a forum in ACP
	 * @event  core.acp_manage_forums_move_content_sql_before	On move content of a forum in ACP
	 * @event  core.delete_posts_in_transaction_before			On delete posts in MCP
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_add_wrw_table($event)
	{
		$table_ary = $event['table_ary'];
		$table_ary[] = $this->table;
		$event['table_ary'] = $table_ary;
	}

	/**
	 * Forks the topics accordingly to the native functionality.
	 *
	 * @event  core.mcp_main_fork_sql_after
	 * @param  \phpbb\event\data		$event		The event object
	 * @return void
	 * @access public
	 */
	public function wrw_mcp_main_fork_sql_after($event)	// Should I implement this one?
	{
		/*

		$topic_id		= $event['row']['topic_id'];
		$post_id		= $event['row']['post_id'];
		$new_topic_id	= $event['new_topic_id'];
		$to_forum_id	= $event['to_forum_id'];
		$new_post_id	= $event['new_post_id'];

		*/
	}
}
