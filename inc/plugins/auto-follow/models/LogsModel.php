<?php 
namespace Plugins\AutoFollow;

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?");

/**
 * Logs model
 *
 * @version 1.0
 * @author Onelab <hello@onelab.co> 
 * 
 */
class LogsModel extends \DataList
{	
	/**
	 * Initialize
	 */
	public function __construct()
	{
		$this->setQuery(\DB::table(TABLE_PREFIX."auto_follow_log"));
	}
}
