<?php 
namespace Plugins\AutoRepost;
const IDNAME = "auto-repost";

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

    $sql = "CREATE TABLE `".TABLE_PREFIX."auto_repost_schedule` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `user_id` INT NOT NULL , 
                `account_id` INT NOT NULL , 
                `target` TEXT NOT NULL , 
                `remove_delay` VARCHAR(20) NOT NULL , 
                `speed` VARCHAR(20) NOT NULL , 
                `is_active` BOOLEAN NOT NULL , 
                `schedule_date` DATETIME NOT NULL , 
                `end_date` DATETIME NOT NULL , 
                `last_action_date` DATETIME NOT NULL , 
                PRIMARY KEY (`id`), 
                INDEX (`user_id`), 
                INDEX (`account_id`)
            ) ENGINE = InnoDB;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_schedule` 
                ADD CONSTRAINT `arpschdl_ibfk_1` FOREIGN KEY (`user_id`) 
                REFERENCES `".TABLE_PREFIX."users`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_schedule` 
                ADD CONSTRAINT `arpschdl_ibfk_2` FOREIGN KEY (`account_id`) 
                REFERENCES `".TABLE_PREFIX."accounts`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";


    $sql .= "CREATE TABLE `".TABLE_PREFIX."auto_repost_log` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `user_id` INT NOT NULL , 
                `account_id` INT NOT NULL , 
                `status` VARCHAR(20) NOT NULL,
                `media_code` VARCHAR(50) NOT NULL,
                `data` TEXT NOT NULL , 
                `date` DATETIME NOT NULL , 
                `is_deleted` BOOLEAN NOT NULL , 
                `remove_date` DATETIME NOT NULL , 
                PRIMARY KEY (`id`), 
                INDEX (`user_id`), 
                INDEX (`account_id`),
                INDEX (`media_code`)
            ) ENGINE = InnoDB;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_log` 
                ADD CONSTRAINT `arplg_ibfk_1` FOREIGN KEY (`user_id`) 
                REFERENCES `".TABLE_PREFIX."users`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_log` 
                ADD CONSTRAINT `arplg_ibfk_2` FOREIGN KEY (`account_id`) 
                REFERENCES `".TABLE_PREFIX."accounts`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";


    $sql .= "CREATE TABLE `".TABLE_PREFIX."auto_repost_remove_schedule` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `user_id` INT NOT NULL , 
                `account_id` INT NOT NULL , 
                `log_id` INT NOT NULL , 
                `date` DATETIME NOT NULL , 
                PRIMARY KEY (`id`), 
                INDEX (`user_id`), 
                INDEX (`account_id`),
                INDEX (`log_id`)
            ) ENGINE = InnoDB;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_remove_schedule` 
                ADD CONSTRAINT `arprschdl_ibfk_1` FOREIGN KEY (`user_id`) 
                REFERENCES `".TABLE_PREFIX."users`(`id`) 
                ON DELETE CASCADE ON UPDATE CASCADE;";

    $sql .= "ALTER TABLE `".TABLE_PREFIX."auto_repost_remove_schedule` 
                ADD CONSTRAINT `arprschdl_ibfk_2` FOREIGN KEY (`account_id`) 
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

    $sql = "DROP TABLE `".TABLE_PREFIX."auto_repost_schedule`;";
    $sql .= "DROP TABLE `".TABLE_PREFIX."auto_repost_log`;";
    $sql .= "DROP TABLE `".TABLE_PREFIX."auto_repost_remove_schedule`;";

    $pdo = \DB::pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
\Event::bind("plugin.remove", __NAMESPACE__ . '\uninstall');


/**
 * Add module as a package options
 * Only users with granted permission
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
                    <?= __('Auto Repost') ?>
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
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/IndexController.php",
        __NAMESPACE__ . "\IndexController"
    ]);
    $GLOBALS[$global_variable_name]->map("GET|POST", "/e/".IDNAME."/[i:id]/?", [
        PLUGINS_PATH . "/". IDNAME ."/controllers/ScheduleController.php",
        __NAMESPACE__ . "\ScheduleController"
    ]);
}
\Event::bind("router.map", __NAMESPACE__ . '\route_maps');



/**
 * Event: navigation.add_special_menu
 */
function navigation($Nav, $AuthUser)
{
    $idname = IDNAME;
    include __DIR__."/views/fragments/navigation.fragment.php";
}
\Event::bind("navigation.add_special_menu", __NAMESPACE__ . '\navigation');



/**
 * Add cron task to repost new posts
 */
function addCronTask()
{
    // Get auto repost schedules
    require_once __DIR__."/models/SchedulesModel.php";
    $Schedules = new SchedulesModel;
    $Schedules->where("is_active", "=", 1)
              ->where("schedule_date", "<=", date("Y-m-d H:i:s"))
              ->where("end_date", ">=", date("Y-m-d H:i:s"))
              ->orderBy("last_action_date", "ASC")
              ->setPageSize(5) // required to prevent server overload
              ->setPage(1)
              ->fetchData();

    if ($Schedules->getTotalCount() < 1) {
        // There is not any active schedule
        return false;
    }

    $as = [__DIR__."/models/ScheduleModel.php", __NAMESPACE__."\ScheduleModel"];
    foreach ($Schedules->getDataAs($as) as $sc) {
        // Set next schedule date
        $reqcount = [
            // speed => number of requests per hour
            "1" => 1, // Very Slow
            "2" => 2, // Slow
            "3" => 3, // Medium
            "4" => 4, // Fast
            "5" => 5  // Very Fast
        ];
        $speed = (int)$sc->get("speed");
        $delta = isset($reqcount[$speed]) && $reqcount[$speed] > 0 
               ? round(3600/$reqcount[$speed]) : rand(720, 7200);
        $next_schedule_timestamp = time() + $delta;
        $sc->set("schedule_date", date("Y-m-d H:i:s", $next_schedule_timestamp))
           ->set("last_action_date", date("Y-m-d H:i:s"))
           ->save();


        // Start data validation
        $Account = \Controller::model("Account", $sc->get("account_id"));
        if (!$Account->isAvailable() || $Account->get("login_required")) {
            // Account is either removed (unexected, external factors)
            // Or login required for this account
            // Deactivate schedule
            $sc->set("is_active", 0)->save();
            continue;
        }

        $User = \Controller::model("User", $sc->get("user_id"));
        if (!$User->isAvailable() || !$User->get("is_active") || $User->isExpired()) {
            // User is not valid
            // Deactivate schedule
            $sc->set("is_active", 0)->save();
            continue;
        }

        if ($User->get("id") != $Account->get("user_id")) {
            // Unexpected, data modified by external factors
            // Deactivate schedule
            $sc->set("is_active", 0)->save();
            continue;
        }

        $targets = @json_decode($sc->get("target"));
        if (!$targets) {
            // Unexpected, data modified by external factors or empty targets
            // Deactivate schedule
            $sc->set("is_active", 0)->save();
            continue;
        }

        // Select random target from the defined target collection
        $i = rand(0, count($targets) - 1);
        $target = $targets[$i];

        if (empty($target->type) || empty($target->id) ||
            !in_array($target->type, ["hashtag", "location", "people"])) 
        {
            // Unexpected invalid target, 
            // data modified by external factors
            $sc->set("is_active", 0)->save();
            continue;   
        }


        // Create a action log (not save yet)
        require_once __DIR__."/models/LogModel.php";
        $Log = new LogModel;
        $Log->set("user_id", $User->get("id"))
            ->set("account_id", $Account->get("id"))
            ->set("status", "error");


        // Login into the account
        try {
            $Instagram = \InstagramController::login($Account);
        } catch (\Exception $e) {
            // Couldn't login into the account

            // Deactivate schedule
            $sc->set("is_active", 0)->save();

            // Log data
            $Log->set("data", $e->getMessage())
                ->save();

            continue;
        }


        // Logged in successfully
        // Now script will try to get feed and like new post
        // And will log result


        $permissions = $User->get("settings.post_types");
        $video_processing = isVideoExtenstionsLoaded() ? true : false;

        $acceptable_media_types = [];
        if (!empty($permissions->timeline_photo)) {
            $acceptable_media_types[] = "1"; // Photo
        }

        if (!empty($permissions->timeline_video)) {
            $acceptable_media_types[] = "2"; // Video
        }

        if (!empty($permissions->album_photo) || !empty($permissions->album_video)) {
            $acceptable_media_types[] = "8"; // Album
        }


        // Find feed item to repost
        $feed_item = null;

        
        if ($target->type == "hashtag") {
            try {
                $feed = $Instagram->hashtag->getFeed(str_replace("#", "", $target->id));
            } catch (\Exception $e) {
                // Couldn't get instagram feed related to the hashtag

                // Log data
                $Log->set("data", $e->getMessage())->save();
                continue;
            }

            $items = array_merge($feed->ranked_items, $feed->items);
            shuffle($items);


            if (isset($items[0]->id)) {
                foreach ($items as $item) {
                    if (empty($item->id)) {
                        // Invalid
                        continue;
                    }

                    if (!in_array($item->media_type, $acceptable_media_types)) {
                        // No permission
                        continue;
                    }

                    if ($item->media_type == 2 && !$video_processing) {
                        // Video processing is not possible now,
                        // FFMPEG is not configured
                        continue;
                    }

                    if ($item->media_type == 8) {
                        $medias = $item->carousel_media;
                        foreach ($medias as $media) {
                            if ($media->media_type == 1 && empty($permissions->album_photo)) {
                                // No permission for photos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && empty($permissions->album_video)) {
                                // No permission for videos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && !$video_processing) {
                                // Video processing is not possible now,
                                // FFMPEG is not configured
                                $is_invalid = true;
                                break;       
                            }
                        }

                        if (!empty($is_invalid)) {
                            continue;
                        }
                    }


                    $_log = new LogModel([
                        "user_id" => $User->get("id"),
                        "account_id" => $Account->get("id"),
                        "media_code" => $item->code,
                        "status" => "success"
                    ]);

                    if ($_log->isAvailable()) {
                        // Already reposted this feed
                        continue;
                    }

                    // Found the media to comment
                    $feed_item = $item;
                    break;
                }
            }
        } else if ($target->type == "location") {
            try {
                $feed = $Instagram->location->getFeed($target->id);
            } catch (\Exception $e) {
                // Couldn't get instagram feed related to the location id

                // Log data
                $Log->set("data", $e->getMessage())->save();
                continue;
            }

            $items = $feed->items;
            shuffle($items);

            if (isset($items[0]->id)) {
                foreach ($items as $item) {
                    if (empty($item->id)) {
                        // Invalid
                        continue;
                    }

                    if (!in_array($item->media_type, $acceptable_media_types)) {
                        // No permission
                        continue;
                    }

                    if ($item->media_type == 2 && !$video_processing) {
                        // Video processing is not possible now,
                        // FFMPEG is not configured
                        continue;
                    }

                    if ($item->media_type == 8) {
                        $medias = $item->carousel_media;
                        foreach ($medias as $media) {
                            if ($media->media_type == 1 && empty($permissions->album_photo)) {
                                // No permission for photos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && empty($permissions->album_video)) {
                                // No permission for videos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && !$video_processing) {
                                // Video processing is not possible now,
                                // FFMPEG is not configured
                                $is_invalid = true;
                                break;       
                            }
                        }

                        if (!empty($is_invalid)) {
                            continue;
                        }
                    }


                    $_log = new LogModel([
                        "user_id" => $User->get("id"),
                        "account_id" => $Account->get("id"),
                        "media_code" => $item->code,
                        "status" => "success"
                    ]);

                    if ($_log->isAvailable()) {
                        // Already reposted this feed
                        continue;
                    }

                    // Found the media to comment
                    $feed_item = $item;
                    break;
                }
            }
        } else if ($target->type == "people") {
            try {
                $feed = $Instagram->timeline->getUserFeed($target->id);
            } catch (\Exception $e) {
                // Couldn't get instagram feed related to the user id

                // Log data
                $Log->set("data", $e->getMessage())->save();
                continue;
            }

            $items = $feed->items;
            shuffle($items);

            if (isset($items[0]->id)) {
                foreach ($items as $item) {
                    if (empty($item->id)) {
                        // Invalid
                        continue;
                    }

                    if (!in_array($item->media_type, $acceptable_media_types)) {
                        // No permission
                        continue;
                    }

                    if ($item->media_type == 2 && !$video_processing) {
                        // Video processing is not possible now,
                        // FFMPEG is not configured
                        continue;
                    }

                    if ($item->media_type == 8) {
                        $medias = $item->carousel_media;
                        foreach ($medias as $media) {
                            if ($media->media_type == 1 && empty($permissions->album_photo)) {
                                // No permission for photos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && empty($permissions->album_video)) {
                                // No permission for videos in album
                                $is_invalid = true;
                                break;       
                            }

                            if ($media->media_type == 2 && !$video_processing) {
                                // Video processing is not possible now,
                                // FFMPEG is not configured
                                $is_invalid = true;
                                break;       
                            }
                        }

                        if (!empty($is_invalid)) {
                            continue;
                        }
                    }


                    $_log = new LogModel([
                        "user_id" => $User->get("id"),
                        "account_id" => $Account->get("id"),
                        "media_code" => $item->code,
                        "status" => "success"
                    ]);

                    if ($_log->isAvailable()) {
                        // Already reposted this feed
                        continue;
                    }

                    // Found the media to comment
                    $feed_item = $item;
                    break;
                }
            }
        }

        if (empty($feed_item)) {
            $Log->set("data", "Couldn't find the new media to repost")->save();
            continue;
        }


        // Download the media
        $media = [];
        if ($feed_item->media_type == 1 && !empty($feed_item->image_versions2->candidates[0]->url)) {
            $media[] = $feed_item->image_versions2->candidates[0]->url;
        } else if ($feed_item->media_type == 2 && !empty($feed_item->video_versions[0]->url)) {
            $media[] = $feed_item->video_versions[0]->url;
        } else if ($feed_item->media_type == 8) {
            foreach ($feed_item->carousel_media as $m) {
                if ($m->media_type == 1 && !empty($m->image_versions2->candidates[0]->url)) {
                    $media[] = $m->image_versions2->candidates[0]->url;
                } else if ($m->media_type == 2 && !empty($m->video_versions[0]->url)) {
                    $media[] = $m->video_versions[0]->url;
                }
            }
        }


        $downloaded_media = [];
        foreach ($media as $m) {
            $url_parts = parse_url($m);
            if (empty($url_parts['path'])) {
                continue;
            }

            $ext = strtolower(pathinfo($url_parts['path'], PATHINFO_EXTENSION));
            $filename = uniqid(readableRandomString(8)."-").".".$ext;
            $downres = file_put_contents(TEMP_PATH . "/". $filename, file_get_contents($m));
            if ($downres) {
                $downloaded_media[] = $filename;
            }
        }

        if (empty($downloaded_media)) {
            $Log->set("data", "Couldn't download the media of the selected post")->save();
            continue;
        }

        $caption = "";
        if (!empty($feed_item->caption->text)) {
            $caption = $feed_item->caption->text;
        }


        // Try to repost
        try {
            if (count($downloaded_media) > 1) {
                $album_media = [];

                foreach ($downloaded_media as $m) {
                    $ext = strtolower(pathinfo($m, PATHINFO_EXTENSION));

                    $album_media[] = [
                        "type" => in_array($ext, ["mp4"]) ? "video" : "photo",
                        "file" => TEMP_PATH."/".$m
                    ];
                }

                $res = $Instagram->timeline->uploadAlbum($album_media, ['caption' => $caption]);
            } else {
                $m = $downloaded_media[0];
                $ext = strtolower(pathinfo($m, PATHINFO_EXTENSION));
                if (in_array($ext, ["mp4"])) {
                    $res = $Instagram->timeline->uploadVideo(TEMP_PATH."/".$m, ["caption" => $caption]);
                } else {
                    $res = $Instagram->timeline->uploadPhoto(TEMP_PATH."/".$m, ["caption" => $caption]);
                }
            }
        } catch (\Exception $e) {
            $Log->set("data", $e->getMessage())->save();
            continue;
        }


        // Reposted media succesfully
        // Save log
        $thumb = null;
        if (isset($feed_item->image_versions2->candidates[0]->url)) {
            $thumb = $feed_item->image_versions2->candidates[0]->url;
        } else if ($feed_item->carousel_media[0]->image_versions2->candidates[0]->url) {
            $thumb = $feed_item->carousel_media[0]->image_versions2->candidates[0]->url;
        }

        $data = [
            "trigger" => $target,
            "grabbed" => [
                "media_id" => $feed_item->id,
                "code" => $feed_item->code,
                "media_type" => $feed_item->media_type,
                "media_thumb" => $thumb
            ],
            "reposted" => [
                "upload_id" => !empty($res->upload_id) ? $res->upload_id : "",
                "media_pk" => !empty($res->media->pk) ? $res->media->pk : "",
                "media_id" => !empty($res->media->id) ? $res->media->id : "",
                "code" => !empty($res->media->code) ? $res->media->code : ""
            ]
        ];

        $remove_date = date("Y-m-d H:i:s", time() + $sc->get("remove_delay"));
        
        $Log->set("status", isset($res->status) && $res->status == "ok" ?  "success" : "error")
            ->set("data", json_encode($data))
            ->set("media_code", $feed_item->code) // This is the grabbed media code, not reposted media code. 
                                                  // Useful for not reposting the same post over the time
            ->set("remove_date", $remove_date)
            ->save();

        if ($res->status == "ok" && $sc->get("remove_delay") > 0 && !empty($res->media->id)) {
            // Add remove task
            require_once __DIR__."/models/RemoveScheduleModel.php";
            $RemoveSchedule = new RemoveScheduleModel;
            $RemoveSchedule->set("user_id", $User->get("id"))
                           ->set("account_id", $Account->get("id"))
                           ->set("log_id", $Log->get("id"))
                           ->set("date", $remove_date)
                           ->save();
        }


        // Remove downloaded media files
        foreach ($downloaded_media as $m) {
            @unlink(TEMP_PATH."/".$m);
        }
    }
}
\Event::bind("cron.add", __NAMESPACE__."\addCronTask");



/**
 * Add cron task to remove the reposted post
 */
function addCronTaskRemove()
{
    // Get auto like schedules
    require_once __DIR__."/models/RemoveSchedulesModel.php";
    $Schedules = new RemoveSchedulesModel;
    $Schedules->where("date", "<=", date("Y-m-d H:i:s"))
              ->setPageSize(5) // required to prevent server overload
              ->setPage(1)
              ->orderBy("id", "ASC")
              ->fetchData();

    if ($Schedules->getTotalCount() < 1) {
        // There is not any active schedule
        return false;
    }

    $as = [__DIR__."/models/RemoveScheduleModel.php", __NAMESPACE__."\RemoveScheduleModel"];
    foreach ($Schedules->getDataAs($as) as $sc) {
        require_once __DIR__."/models/LogModel.php";
        $Log = new LogModel($sc->get("log_id"));
        if (!$Log->isAvailable() || 
            !$Log->get("data.reposted.media_id") ||
            $Log->get("status") != "success") {
            // Unexpected, external factors
            // Remove remove-schedule data
            $sc->delete();
        }


        // Calculate remove_delay in seconds
        $remove_delay = strtotime($sc->get("date")) - strtotime($Log->get("date"));
        if ($remove_delay < 900) {
            $remove_delay = 14400; // 4 hours
        }


        $Account = \Controller::model("Account", $sc->get("account_id"));
        if (!$Account->isAvailable() || $Account->get("login_required")) {
            // Account is either removed (unexected, external factors)
            // Or login required for this account
            // Postpone the remove-schedule
            $sc->set("date", date("Y-m-d H:i:s", time() + $remove_delay))
               ->save();
            continue;
        }


        $User = \Controller::model("User", $sc->get("user_id"));
        if (!$User->isAvailable() || !$User->get("is_active") || $User->isExpired()) {
            // User is not valid
            // Postpone the remove-schedule
            $sc->set("date", date("Y-m-d H:i:s", time() + $remove_delay))
               ->save();
            continue;
        }


        if ($User->get("id") != $Account->get("user_id")) {
            // Unexpected, data modified by external factors
            // Remove remove-schedule
            $sc->delete();
            continue;
        }


        // Login into the account
        try {
            $Instagram = \InstagramController::login($Account);
        } catch (\Exception $e) {
            // Couldn't login into the account

            // Postpone the remove-schedule
            $sc->set("date", date("Y-m-d H:i:s", time() + $remove_delay))
               ->save();

            continue;
        }


        // Logged in successfully
        // Now script will try to send the remove request
        // And will log result
        $media_type_id = $Log->get("data.grabbed.media_type");
        if ($media_type_id == "2") {
            $media_type = "VIDEO";
        } else if ($media_type_id == "8") {
            $media_type = "ALBUM";
        } else {
            $media_type = "PHOTO";
        }

        try {
            $res = $Instagram->media->delete($Log->get("data.reposted.media_id"), $media_type);
        } catch (\Exception $e) {
            // Couldn't remove the media (might be removed already manually)
            // Remove the schedule
            $sc->delete();
            continue;
        }

        if ($res->status != "ok" || empty($res->did_delete)) {
            // Couldn't remove the media (might be removed already manually)
            // Remove the schedule
            $sc->delete();  
        } 


        // Post removed successfully, log the 
        $Log->set("is_deleted", 1)
            ->set("remove_date", date("Y-m-d H:i:s"))
            ->save();

        // Remove the schedule
        // There is no need to it anymore
        $sc->delete();
    }
}
\Event::bind("cron.add", __NAMESPACE__."\addCronTaskRemove");
