<?php
namespace Plugins\WelcomeDM;

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?");

/**
 * Schedule Controller
 */
class ScheduleController extends \Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $this->setVariable("idname", "welcomedm");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }

        $user_modules = $AuthUser->get("settings.modules");
        if (!is_array($user_modules) || !in_array($this->getVariable("idname"), $user_modules)) {
            // Module is not accessible to this user
            header("Location: ".APPURL."/post");
            exit;
        }


        // Get account
        $Account = \Controller::model("Account", $Route->params->id);
        if (!$Account->isAvailable() || 
            $Account->get("user_id") != $AuthUser->get("id")) 
        {
            header("Location: ".APPURL."/e/".$this->getVariable("idname"));
            exit;
        }
        $this->setVariable("Account", $Account);

        // Get Schedule
        require_once PLUGINS_PATH."/".$this->getVariable("idname")."/models/ScheduleModel.php";
        $Schedule = new ScheduleModel([
            "account_id" => $Account->get("id"),
            "user_id" => $Account->get("user_id")
        ]);
        $this->setVariable("Schedule", $Schedule);

        if (\Input::post("action") == "save") {
            $this->save();
        }

        $this->view(PLUGINS_PATH."/".$this->getVariable("idname")."/views/schedule.php", null);
    }


    /**
     * Save schedule
     * @return mixed 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");
        $Schedule = $this->getVariable("Schedule");

        // Emojione Client
        $Emojione = new \Emojione\Client(new \Emojione\Ruleset());
        // Messages
        $raw_messages = @json_decode(\Input::post("messages"));
        $valid_messages = [];
        if ($raw_messages) {
            foreach ($raw_messages as $m) {
                $valid_messages[] = $Emojione->toShort($m);
            }
        }
        $messages = json_encode($valid_messages);

        // Speed
        $speed = (int)\Input::post("speed");
        if ($speed < 0 || $speed > 5) {
            $speed = 0;
        }
        
        $is_active = \Input::post("is_active") ? 1 : 0;
        $end_date = count($valid_messages) > 0 
                  ? "2030-12-12 23:59:59" : date("Y-m-d H:i:s");

        $Schedule->set("user_id", $AuthUser->get("id"))
                 ->set("account_id", $Account->get("id"))
                 ->set("messages", $messages)
                 ->set("speed", $speed)
                 ->set("is_active", $is_active)
                 ->set("schedule_date", date("Y-m-d H:i:s"))
                 ->set("end_date", $end_date)
                 ->set("last_action_date", date("Y-m-d H:i:s"))
                 ->save();

        $this->resp->msg = __("Changes saved!");
        $this->resp->result = 1;
        $this->jsonecho();
    }
}
