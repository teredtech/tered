<?php
namespace Plugins\AutoFollow;

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?");

/**
 * Settings Controller
 */
class SettingsController extends \Controller
{
    /**
     * idname of the plugin for internal use
     */
    const IDNAME = 'auto-follow';


    /**
     * Process
     * @return null
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $this->setVariable("idname", self::IDNAME);

        // Auth
        if (!$AuthUser || !$AuthUser->isAdmin()){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }

        // Plugin settings
        $this->setVariable("Settings", namespace\settings());

        // Actions
        if (\Input::post("action") == "save") {
            $this->save();
        }

        $this->view(PLUGINS_PATH."/".self::IDNAME."/views/settings.php", null);
    }


    /**
     * Save plugin settings
     * @return boolean 
     */
    private function save()
    {  
        $Settings = $this->getVariable("Settings");
        if (!$Settings->isAvailable()) {
            // Settings is not available yet
            $Settings->set("name", "plugin-auto-follow-settings");
        }

        $speeds = [
            "very_slow" => (int)\Input::post("speed-very-slow"),
            "slow" => (int)\Input::post("speed-slow"),
            "medium" => (int)\Input::post("speed-medium"),
            "fast" => (int)\Input::post("speed-fast"),
            "very_fast" => (int)\Input::post("speed-very-fast"),
        ];

        foreach ($speeds as $key => $value) {
            if ($value < 1) {
                $speeds[$key] = 1;
            }

            if ($value > 60) {
                $speeds[$key] = 60;
            }
        }


        $random_delay = (bool)\Input::post("random_delay");
        
        $Settings->set("data.speeds", $speeds)
                 ->set("data.random_delay", $random_delay)
                 ->save();

        $this->resp->result = 1;
        $this->resp->msg = __("Changes saved!");
        $this->jsonecho();

        return $this;
    }
}
