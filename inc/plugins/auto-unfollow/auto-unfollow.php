<?php 
namespace Plugins\AutoUnfollow;
const IDNAME = "auto-unfollow";

// Disable direct access
if (!defined('APP_VERSION')) 
    die("Yo, what's up?"); 


/**
 * Event: plugin.install
 */
function install($Plugin)
{
    if ($Plugin->get("idname") != IDNAME) {
        return false;
    }

    $sql = "CREATE TABLE `".TABLE_PREFIX."auto_unfollow_schedule` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `user_id` INT NOT NULL , 
                `account_id` INT NOT NULL , 
                `speed` VARCHAR(20) NOT NULL , 
                `daily_pause` BOOLEAN NOT NULL, 
                `daily_pause_from` TIME NOT NULL, 
                `daily_pause_to` TIME NOT NULL,
                `keep_followers` BOOLEAN NOT NULL , 
                `whitelist` TEXT NOT NULL , 
                `source` VARCHAR(100) NOT NULL ,
                `is_active` BOOLEAN NOT NULL , 
                `schedule_date` DATETIME NOT NULL , 
                `end_date` DATETIME NOT NULL , 
                `last_action_date` DATETIME NOT NULL , 
                `data` TEXT NOT NULL,
                PRIMARY KEY (`id`), 
                INDEX (`user_id`), 
                INDEX (`account_id`)
            ) ENGINE = InnoDB;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_unfollow_schedule` 
                ADD CONSTRAINT `aufschdl_ibfk_1` FOREIGN KEY (`user_id`) 
                REFERENCES `".TABLE_PREFIX."users`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_unfollow_schedule` 
                ADD CONSTRAINT `aufschdl_ibfk_2` FOREIGN KEY (`account_id`) 
                REFERENCES `".TABLE_PREFIX."accounts`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";


    $sql .= "CREATE TABLE `".TABLE_PREFIX."auto_unfollow_log` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `user_id` INT NOT NULL , 
                `account_id` INT NOT NULL , 
                `status` VARCHAR(20) NOT NULL,
                `unfollowed_user_pk` VARCHAR(50) NOT NULL,
                `data` TEXT NOT NULL , 
                `date` DATETIME NOT NULL , 
                PRIMARY KEY (`id`), 
                INDEX (`user_id`), 
                INDEX (`account_id`),
                INDEX (`unfollowed_user_pk`)
            ) ENGINE = InnoDB;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_unfollow_log` 
                ADD CONSTRAINT `auflg_ibfk_1` FOREIGN KEY (`user_id`) 
                REFERENCES `".TABLE_PREFIX."users`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_unfollow_log` 
                ADD CONSTRAINT `auflg_ibfk_2` FOREIGN KEY (`account_id`) 
                REFERENCES `".TABLE_PREFIX."accounts`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $pdo = \DB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
\Event::bind("plugin.install", __NAMESPACE__ . '\install');



/**
 * Event: plugin.remove
 */
function uninstall($Plugin)
{
    if ($Plugin->get("idname") != IDNAME) {
        return false;
    }

    // Remove plugin settings
    $Settings = \Controller::model("GeneralData", "plugin-auto-unfollow-settings");
    $Settings->remove();

    $sql = "DROP TABLE `".TABLE_PREFIX."auto_unfollow_schedule`;";
    $sql .= "DROP TABLE `".TABLE_PREFIX."auto_unfollow_log`;";

    $pdo = \DB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
\Event::bind("plugin.remove", __NAMESPACE__ . '\uninstall');


/**
 * Add module as a package options
 * Only users with correct permission
 * Will be able to use module
 * 
 * @param array $package_modules An array of currently active 
 *                               modules of the package
 */
function add_module_option($package_modules)
{
    $config = include __DIR__."/config.php";
    ?>
        <div class="mt-15">
            <label>
                <input type="checkbox" 
                       class="checkbox" 
                       name="modules[]" 
                       value="<?= IDNAME ?>" 
                       <?= in_array(IDNAME, $package_modules) ? "checked" : "" ?>>
                <span>
                    <span class="icon unchecked">
                        <span class="mdi mdi-check"></span>
                    </span>
                    <?= __('Auto Unfollow') ?>
                </span>
            </label>
        </div>
    <?php
}
\Event::bind("package.add_module_option", __NAMESPACE__ . '\add_module_option');





/**
 * Map routes
 */
function route_maps($global_variable_name)
{
    // Settings (admin only)
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/settings/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/SettingsController.php",
        __NAMESPACE__ . "\SettingsController"
    ]);

    // Index
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/IndexController.php",
        __NAMESPACE__ . "\IndexController"
    ]);

    // Schedule
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/[i:id]/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/ScheduleController.php",
        __NAMESPACE__ . "\ScheduleController"
    ]);

    // Log
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/[i:id]/log/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/LogController.php",
        __NAMESPACE__ . "\LogController"
    ]);
}
\Event::bind("router.map", __NAMESPACE__ . '\route_maps');



/**
 * Event: navigation.add_special_menu
 */
function navigation($Nav, $AuthUser)
{
    $idname = IDNAME;
    include "views/fragments/navigation.fragment.php";
}
\Event::bind("navigation.add_special_menu", __NAMESPACE__ . '\navigation');



/**
 * Add cron task to unfollow users
 */
function addCronTask()
{
    require_once __DIR__."/models/SchedulesModel.php";
    require_once __DIR__."/models/LogModel.php";


    // Get auto unfollow schedules
    $Schedules = new SchedulesModel;
    $Schedules->where("is_active", "=", 1)
              ->where("schedule_date", "<=", date("Y-m-d H:i:s"))
              ->where("end_date", ">=", date("Y-m-d H:i:s"))
              ->orderBy("last_action_date", "ASC")
              ->setPageSize(10) // required to prevent server overload
              ->setPage(1)
              ->fetchData();

    if ($Schedules->getTotalCount() < 1) {
        return false;
    }

    $settings = namespace\settings();
    $default_speeds = [
        "very_slow" => 1,
        "slow" => 2,
        "medium" => 3,
        "fast" => 4,
        "very_fast" => 5,
    ];
    $speeds = $settings->get("data.speeds");
    if (empty($speeds)) {
        $speeds = [];
    } else {
        $speeds = json_decode(json_encode($speeds), true);
    }
    $speeds = array_merge($default_speeds, $speeds);


    $as = [__DIR__."/models/ScheduleModel.php", __NAMESPACE__. "\ScheduleModel"];
    foreach ($Schedules->getDataAs($as) as $sc) {
        $Log = new LogModel;
        $Account = \Controller::model("Account", $sc->get("account_id"));
        $User = \Controller::model("User", $sc->get("user_id"));

        $whitelist_pks = [];
        $follower_pks = [];
        $source = "all";

        // Define source
        $auto_follow_log_model_path = PLUGINS_PATH."/auto-follow/models/LogModel.php";
        if ($sc->get("source") == "auto-follow" && 
            in_array("auto-follow", $User->get("settings.modules")) &&
            isset($auto_follow_log_model_path) && 
            in_array("auto-follow", array_keys($GLOBALS["_PLUGINS_"]))) 
        {
            require_once PLUGINS_PATH."/auto-follow/models/LogModel.php";
            $source = "auto-follow";
        }

        // Calculate next schedule datetime...
        if (isset($speeds[$sc->get("speed")]) && (int)$speeds[$sc->get("speed")] > 0) {
            $speed = (int)$speeds[$sc->get("speed")];
            $delta = round(3600/$speed);

            if ($settings->get("data.random_delay")) {
                $delay = rand(0, 300);
                $delta += $delay;
            }
        } else {
            $delta = rand(720, 7200);
        }

        $next_schedule = date("Y-m-d H:i:s", time() + $delta);
        if ($sc->get("daily_pause")) {
            $pause_from = date("Y-m-d")." ".$sc->get("daily_pause_from");
            $pause_to = date("Y-m-d")." ".$sc->get("daily_pause_to");
            if ($pause_to <= $pause_from) {
                // next day
                $pause_to = date("Y-m-d", time() + 86400)." ".$sc->get("daily_pause_to");
            }

            if ($next_schedule > $pause_to) {
                // Today's pause interval is over
                $pause_from = date("Y-m-d H:i:s", strtotime($pause_from) + 86400);
                $pause_to = date("Y-m-d H:i:s", strtotime($pause_to) + 86400);
            }

            if ($next_schedule >= $pause_from && $next_schedule <= $pause_to) {
                $next_schedule = $pause_to;
            }
        }

        $sc->set("schedule_date", $next_schedule)
           ->set("last_action_date", date("Y-m-d H:i:s"))
           ->save();


        // Set default values for the log...
        $Log->set("user_id", $User->get("id"))
            ->set("account_id", $Account->get("id"))
            ->set("status", "error");


        if (!$Account->isAvailable() || $Account->get("login_required")) {
            // Account is either removed (unexpected, external factors)
            // Or login required for this account
            // Deactivate schedule
            $sc->set("is_active", 0)->save();

            // Log data
            $Log->set("data.error.msg", "Activity has been stopped")
                ->set("data.error.details", "Re-login is required for the account.")
                ->save();
            continue;
        }

        // Check user account
        if (!$User->isAvailable() || !$User->get("is_active") || $User->isExpired()) {
            // User is not valid
            // Deactivate schedule
            $sc->set("is_active", 0)->save();

            // Log data
            $Log->set("data.error.msg", "Activity has been stopped")
                ->set("data.error.details", "User account is either disabled or expred.")
                ->save();
            continue;
        }

        if ($User->get("id") != $Account->get("user_id")) {
            // Unexpected, data modified by external factors
            // Deactivate schedule
            $sc->set("is_active", 0)->save();
            continue;
        }


        try {
            $Instagram = \InstagramController::login($Account);
        } catch (\Exception $e) {
            // Couldn't login into the account

            // Deactivate schedule
            $sc->set("is_active", 0)->save();

            // Log data
            // Log data
            $Log->set("data.error.msg", "Activity has been stopped")
                ->set("data.error.details", $e->getMessage())
                ->save();

            continue;
        }


        // Logged in successfully
        // Now script will try to get followings and unfollow a user
        // And will log result

        

        // Find username to unfollow
        try {
            $following = $Instagram->people->getSelfFollowing();
        } catch (\Exception $e) {
            // Couldn't get following accounts

            // Log data
            $Log->set("data.error.msg", "Couldn't get the followed accounts")
                ->set("data.error.details", $e->getMessage())
                ->save();
            continue;
        }
        

        if (empty($following->users)) {
            // Couldn't find any user to unfollow
            // Probably user is not following anyone right now
            $Log->set("status", "error")
                ->set("data.error.msg", "Couldn't find any user to unfollow")
                ->set("data.error.details", "There is not any user to unfollow")
                ->save();
            continue;
        }


        // Reverse order users
        $following_users = array_reverse($following->users);

        // Get whitelist
        $whitelist = json_decode($sc->get("whitelist"));
        foreach ($whitelist as $u) {
            if (!empty($u->id)) {
                $whitelist_pks[] = $u->id;
            }
        }

        // Get followers
        if ($sc->get("keep_followers")) {
            try {
                // Get my followers
                $followers = $Instagram->people->getSelfFollowers();
            } catch (\Exception $e) {
                // Couldn't get following accounts

                // Log data
                $Log->set("data.error.msg", "Couldn't get the followers")
                    ->set("data.error.details", $e->getMessage())
                    ->save();
                continue;
            }


            if (!empty($followers->users)) {
                foreach ($followers->users as $user) {
                    $follower_pks[] = $user->pk;
                }
            }
        }


        // Find user to unfollow
        $unfollow_pk = null;
        $unfollow_username = null;
        $unfollow_full_name = null;
        $unfollow_profile_pic_url = null;

        foreach ($following_users as $i => $usr) {
            $pk = $usr->pk;

            if (in_array($pk, $whitelist_pks) || in_array($pk, $follower_pks)) {
                unset($following_users[$i]);
                continue;
            }

            if ($source == "auto-follow") {
                $_log = new \Plugins\AutoFollow\LogModel([
                    "user_id" => $User->get("id"),
                    "account_id" => $Account->get("id"),
                    "followed_user_pk" => $pk,
                    "status" => "success"
                ]);

                if (!$_log->isAvailable()) {
                    // Not followed by auto follow module
                    continue;
                }
            }


            // Check if unfollow request has been sent before
            $_log = new LogModel([
                "user_id" => $User->get("id"),
                "account_id" => $Account->get("id"),
                "unfollowed_user_pk" => $pk,
                "status" => "success"
            ]);

            if ($_log->isAvailable()) {
                // Unfollowed before
                continue;
            }


            $unfollow_pk = $usr->pk;
            $unfollow_username = $usr->username;
            $unfollow_full_name = $usr->full_name;
            $unfollow_profile_pic_url = $usr->profile_pic_url;

            break;                
        }

        if (empty($unfollow_pk)) {
            $Log->set("status", "error")
                ->set("data.error.msg", "Couldn't find any user to unfollow")
                ->set("data.error.details", "There is not any user to unfollow according to the task settings")
                ->save();

            // Check auto stop
            // Get latest activity logs
            $ActivityLog = \Controller::model([PLUGINS_PATH."/".IDNAME."/models/LogsModel.php", 
                                              __NAMESPACE__."\LogsModel"]);
            $ActivityLog->setPageSize(3)
                        ->setPage(\Input::get("page"))
                        ->where("user_id", "=", $User->get("id"))
                        ->where("account_id", "=", $Account->get("id"))
                        ->orderBy("id","DESC")
                        ->fetchData();
            if ($ActivityLog->getTotalCount() > 0) {
                $not_found_count = 0;

                $as = [PLUGINS_PATH."/".IDNAME."/models/LogModel.php", 
                       __NAMESPACE__."\LogModel"];
                foreach ($ActivityLog->getDataAs($as) as $l) {
                    if ($l->get("data.error.msg") == "Couldn't find any user to unfollow") {
                        $not_found_count++;
                    }
                }

                if ($not_found_count >= 3) {
                    // Stop the task
                    $sc->set("is_active", 0)->save();

                    $Log->set("data.error.msg", "Activity has been stopped")
                        ->set("data.error.details", "There is not any user to unfollow according to the task settings")
                        ->save();
                }
            }

            continue;
        }

        
        // Unfollow the found account
        try {
            $resp = $Instagram->people->unfollow($unfollow_pk);
        } catch (\Exception $e) {
            $Log->set("data.error.msg", "Couldn't unfollow the user")
                ->set("data.error.details", $e->getMessage())
                ->save();
            continue;
        }

        if ($resp->status != "ok") {
            $Log->set("data.error.msg", "Couldn't unfollow the user")
                ->set("data.error.details", "Something went wrong")
                ->save();
            continue;   
        }


        // Unfollowed the account successfully
        $Log->set("status", "success")
            ->set("data.unfollowed", [
                "pk" => $unfollow_pk,
                "username" => $unfollow_username,
                "full_name" => $unfollow_full_name,
                "profile_pic_url" => $unfollow_profile_pic_url
            ])
            ->set("unfollowed_user_pk", $unfollow_pk)
            ->save();
    }
}
\Event::bind("cron.add", __NAMESPACE__."\addCronTask");



/**
 * Get Plugin Settings
 * @return \GeneralDataModel 
 */
function settings()
{
    $settings = \Controller::model("GeneralData", "plugin-auto-unfollow-settings");
    return $settings;
}