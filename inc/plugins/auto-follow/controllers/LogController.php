<?php
namespace Plugins\AutoFollow;

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?");

/**
 * Log Controller
 */
class LogController extends \Controller
{
    /**
     * idname of the plugin for internal use
     */
    const IDNAME = 'auto-follow';

    
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $this->setVariable("idname", self::IDNAME);

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }

        $user_modules = $AuthUser->get("settings.modules");
        if (!is_array($user_modules) || !in_array(self::IDNAME, $user_modules)) {
            // Module is not accessible to this user
            header("Location: ".APPURL."/post");
            exit;
        }


        // Get account
        $Account = \Controller::model("Account", $Route->params->id);
        if (!$Account->isAvailable() || 
            $Account->get("user_id") != $AuthUser->get("id")) 
        {
            header("Location: ".APPURL."/e/".self::IDNAME);
            exit;
        }
        $this->setVariable("Account", $Account);


        // Get Schedule
        require_once PLUGINS_PATH."/".self::IDNAME."/models/ScheduleModel.php";
        $Schedule = new ScheduleModel([
            "account_id" => $Account->get("id"),
            "user_id" => $Account->get("user_id")
        ]);
        $this->setVariable("Schedule", $Schedule);


        // Get Activity Log
        $ActivityLog = \Controller::model([PLUGINS_PATH."/".self::IDNAME."/models/LogsModel.php", 
                                   __NAMESPACE__."\LogsModel"]);
        $ActivityLog->setPageSize(20)
                    ->setPage(\Input::get("page"))
                    ->where("user_id", "=", $AuthUser->get("id"))
                    ->where("account_id", "=", $Account->get("id"))
                    ->orderBy("id","DESC")
                    ->fetchData();

        $Logs = [];
        $as = [PLUGINS_PATH."/".self::IDNAME."/models/LogModel.php", 
               __NAMESPACE__."\LogModel"];
        foreach ($ActivityLog->getDataAs($as) as $l) {
            $Logs[] = $l;
        }

        $this->setVariable("ActivityLog", $ActivityLog)
             ->setVariable("Logs", $Logs);


        // View
        $this->view(PLUGINS_PATH."/".self::IDNAME."/views/log.php", null);
    }
}