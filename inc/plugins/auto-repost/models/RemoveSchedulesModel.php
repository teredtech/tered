<?php 
namespace Plugins\AutoRepost;

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?");

/**
 * Remove Schedules model
 *
 * @version 1.0
 * @author Onelab <hello@onelab.co> 
 * 
 */
class RemoveSchedulesModel extends \DataList
{	
	/**
	 * Initialize
	 */
	public function __construct()
	{
		$this->setQuery(\DB::table(TABLE_PREFIX."auto_repost_remove_schedule"));
	}
}
