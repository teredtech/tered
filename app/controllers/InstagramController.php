<?php
/**
 * Instagram Controller
 */
class InstagramController extends Controller
{
    /**
     * Publish the $Post to the Instagram
     * @param  PostModel $Post 
     * @return string          Post media code
     */
    public static function publish($Post)
    {   
        // Check availability
        if (!$Post->isAvailable()) {
            // Probably post has been removed manually
            throw new Exception(__("Post is not available!"));
        }


        // Check status
        if ($Post->get("status") != "publishing") {
            // Setting post status to "publishing" before passing it 
            // to this controller is in responsibility of
            // PostController or CronController
            // 
            // Data has been modified by external factors
            throw new Exception(__("Post status is not valid!"));
        }


        // Check type
        $type = $Post->get("type");
        if (!in_array($type, ["timeline", "story", "album"])) {
            // Validating post type before passing it 
            // to this controller is in responsibility of PostController
            // 
            // Data has been modified by external factors

            $msg = __("Post type is not valid!");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        }

        
        // Check user
        $User = Controller::model("User", $Post->get("user_id"));
        if (!$User->isAvailable() || !$User->get("is_active") || $User->isExpired()) {
            $msg = __("Your access to the script has been disabled!");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        }


        // Check account
        $Account = Controller::model("Account", $Post->get("account_id"));
        if (!$Account->isAvailable()) {
            $msg = __("Account is not available.");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update($msg);
            throw new Exception($msg);
        }
        if ($Account->get("login_required")) {
            $msg = __("Re-login required for %s", $Account->get("username"));
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        }

        // Check media ids
        $user_files_dir = ROOTPATH . "/assets/uploads/" . $User->get("id");
        $media_ids = explode(",", $Post->get("media_ids"));
        foreach ($media_ids as $i => $id) {
            if ((int)$id < 1) {
                unset($media_ids[$i]);
            } else {
                $id = (int)$id;
            }
        }

        $query = DB::table(TABLE_PREFIX.TABLE_FILES)
               ->where("user_id", "=", $User->get("id"))
               ->whereIn("id", $media_ids);
        $res = $query->get();

        $valid_media_ids = [];
        $media_data = [];
        foreach ($res as $m) {
            $ext = strtolower(pathinfo($m->filename, PATHINFO_EXTENSION));

            if (file_exists($user_files_dir."/".$m->filename) &&
                in_array($ext, ["jpeg", "jpg", "png", "mp4"])) {
                $valid_media_ids[] = $m->id;
                $media_data[$m->id] = $m;
            }
        }

        foreach ($media_ids as $i => $id) {
            if (!in_array($id, $valid_media_ids)) {
                unset($media_ids[$i]);
            }
        }

        if ($type == "album" && count($media_ids) < 2) {
            $msg = __("At least 2 photo or video is required for the album post.");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        } else if ($type == "story" && count($media_ids) < 1) {
            $msg = __("Couldn't find selected media for the story");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        } else if ($type == "timeline" && count($media_ids) < 1) {
            $msg = __("Couldn't find selected media for the post");
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        }

        switch ($type) {
            case "timeline":
            case "story":
                $media_ids = array_slice($media_ids, 0, 1);
                break;

            case "album":
                $media_ids = array_slice($media_ids, 0, 10);
                break;
            
            default:
                $media_ids = array_slice($media_ids, 0, 1);
                break;
        }

        // Check user permissions
        foreach ($media_ids as $id) {
            $media = $media_data[$id];
            $ext = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));

            if (in_array($ext, ["mp4"])) {
                if (!isVideoExtenstionsLoaded()) {
                    $msg = __("It's not possible to post video files right now!");
                    $Post->set("status", "failed")
                         ->set("data", $msg)
                         ->update();
                    throw new Exception($msg);
                }

                $permission = "settings.post_types.".$type."_video";
            } else if (in_array($ext, ["jpg", "jpeg", "png"])) {
                $permission = "settings.post_types.".$type."_photo";
            } else {
                $msg = __("Oops! An error occured. Please try again later!");
                $Post->set("status", "failed")
                     ->set("data", $msg)
                     ->update();
                throw new Exception($msg);
            }

            if (!$User->get($permission)) {
                $permission_errors = [
                    "settings.post_types.timeline_video" => __("You don't have a permission for video posts."),
                    "settings.post_types.story_video" => __("You don't have a permission for story videos."),
                    "settings.post_types.album_video" => __("You don't have a permission for videos in album."),
                    "settings.post_types.timeline_photo" => __("You don't have a permission for photo posts."),
                    "settings.post_types.story_photo" => __("You don't have a permission for story photos."),
                    "settings.post_types.album_photo" => __("You don't have a permission for photos in album.")
                ];

                if (isset($permission_errors[$permission])) {
                    $msg = $permission_errors[$permission];
                } else {
                    $msg = __("You don't have a permission for this kind of post.");
                }

                $Post->set("status", "failed")
                     ->set("data", $msg)
                     ->update();
                throw new Exception($msg);
            }
        }

        
        // Login
        try {
            $Instagram = self::login($Account);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();
            throw new Exception($msg);
        }



        // Caption
        $Emojione = new \Emojione\Client(new \Emojione\Ruleset());
        $caption = $Emojione->shortnameToUnicode($Post->get("caption"));
        $caption = mb_substr($caption, 0, 2200);

        // Check spintax permission
        if ($User->get("settings.spintax")) {
            $caption = Spintax::process($caption);
        }


        try {
            if ($type == "timeline") {
                $media = $media_data[$media_ids[0]];
                $ext = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));
                $file_path = $user_files_dir."/".$m->filename;

                if (in_array($ext, ["mp4"])) {
                    if (!isVideoExtenstionsLoaded()) {
                        $msg = __("It's not possible to post video files!");
                        $Post->set("status", "failed")
                             ->set("data", $msg)
                             ->update();
                        throw new Exception($msg);
                    }
                    $resp = $Instagram->timeline->uploadVideo($file_path, ["caption" => $caption]);
                } else {
                    $img = new \InstagramAPI\MediaAutoResizer($file_path, [
                        "targetFeed" => \InstagramAPI\Constants::FEED_TIMELINE,
                        "operation" => \InstagramAPI\MediaAutoResizer::CROP
                    ]);
                    $resp = $Instagram->timeline->uploadPhoto($img->getFile(), ["caption" => $caption]);
                }
            } else if ($type == "story") {
                $media = $media_data[$media_ids[0]];
                $ext = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));
                $file_path = $user_files_dir."/".$m->filename;

                if (in_array($ext, ["mp4"])) {
                    if (!isVideoExtenstionsLoaded()) {
                        $msg = __("It's not possible to post video files!");
                        $Post->set("status", "failed")
                             ->set("data", $msg)
                             ->update();
                        throw new Exception($msg);
                    }
                    $resp = $Instagram->story->uploadVideo($file_path, ["caption" => $caption]);
                } else {
                    $img = new \InstagramAPI\MediaAutoResizer($file_path, [
                        "targetFeed" => \InstagramAPI\Constants::FEED_STORY,
                        "operation" => \InstagramAPI\MediaAutoResizer::CROP,
                        "minAspectRatio" => \InstagramAPI\MediaAutoResizer::BEST_MIN_STORY_RATIO,
                        "maxAspectRatio" => \InstagramAPI\MediaAutoResizer::BEST_MAX_STORY_RATIO
                    ]);
                    $resp = $Instagram->story->uploadPhoto($img->getFile(), ["caption" => $caption]);
                }
            } else if ($type == "album") {
                $album_media = [];
                $temp_files_handlers = [];

                foreach ($media_ids as $id) {
                    $media = $media_data[$id];
                    $ext = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));
                    $file_path = $user_files_dir."/".$media->filename;

                    if (in_array($ext, ["mp4"])) {
                        if (!isVideoExtenstionsLoaded()) {
                            $msg = __("It's not possible to post video files!");
                            $Post->set("status", "failed")
                                 ->set("data", $msg)
                                 ->update();
                            throw new Exception($msg);
                        }
                        $media_type = "video";
                    } else {
                        $media_type = "photo";

                        $temp_files_handlers[] = new \InstagramAPI\MediaAutoResizer($file_path, [
                            "targetFeed" => \InstagramAPI\Constants::FEED_TIMELINE_ALBUM,
                            "operation" => \InstagramAPI\MediaAutoResizer::CROP,
                            "minAspectRatio" => 1,
                            "maxAspectRatio" => 1
                        ]);
                        $file_path = $temp_files_handlers[count($temp_files_handlers) - 1]->getFile();
                    }

                    $album_media[] = [
                        "type" => $media_type,
                        "file" => $file_path
                    ];
                }

                $resp = $Instagram->timeline->uploadAlbum($album_media, ['caption' => $caption]);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();

            // Extract full file path from exception messages
            // Found in configuration and validation error messages
            preg_match('/"[^"]+"/', $msg, $matches);
            if ($matches && strpos($matches[0], ROOTPATH) !== false) {
                $invalid_file_path = $matches[0];
                $invalid_file_url = str_replace(ROOTPATH, APPURL, $invalid_file_path);
                $invalid_file_name = basename($invalid_file_path, '"');

                $human_readable_file_name = explode("-", $invalid_file_name, 2);
                $human_readable_file_name = $human_readable_file_name[0];

                $msg = preg_replace('/"[^"]+"/', "<a href=".$invalid_file_url." target='_blank' class='file-link' data-file='".$invalid_file_name."'>".$human_readable_file_name."</a>", $msg);
            }

            $Post->set("status", "failed")
                 ->set("data", $msg)
                 ->update();


            throw new Exception($msg);
        }


        
        $ig_media_code = !empty($resp->media->code) ? $resp->media->code : "";
        $data = [
            "upload_id" => !empty($resp->upload_id) ? $resp->upload_id : "",
            "pk" => !empty($resp->media->pk) ? $resp->media->pk : "",
            "id" => !empty($resp->media->id) ? $resp->media->id : "",
            "code" => $ig_media_code
        ];
        $Post->set("status", "published")
             ->set("data", json_encode($data))
             ->set("publish_date", date("Y-m-d H:i:s"))
             ->update();

        return $ig_media_code;   
    }


    /**
     * Get account info
     * @param  AccountModel $Account 
     * @return array                 Account information
     */
    public static function getAccountInfo($Account)
    {
        // Login
        try {
            $Instagram = self::login($Account);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        try {
            $resp = $Instagram->people->getSelfInfo();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        
        return $resp;
    }


    /**
     * login 
     * If account logged in recently then don't login
     * 
     * @param  AccountModel $Account 
     * @return mixed    
     */
    public static function login($Account)
    {
        // Check availability
        if (!$Account->isAvailable()) {
            throw new Exception(__("Account is not available."));
        }

        // Check is re-login required
        if ($Account->get("login_required")) {
            throw new Exception(__("Re-login required for %s", $Account->get("username")));
        }

        // Decrypt pass.
        try {
            $password = \Defuse\Crypto\Crypto::decrypt($Account->get("password"), 
                        \Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));
        } catch (Exception $e) {
            throw new Exception(__("Encryption error"));
        }


        // Temporary directory for image and video processing
        $temp_dir = TEMP_PATH;
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir);
        } 
        
        // Setup Instagram Client
        \InstagramAPI\MediaAutoResizer::$defaultTmpPath = $temp_dir;
        \InstagramAPI\Utils::$defaultTmpPath = $temp_dir;

        // Instagram Client
        $storage_config = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$Account->get("user_id")."/",
        ];
        $Instagram = new \InstagramAPI\Instagram(false, false, $storage_config);
        $Instagram->setVerifySSL(SSL_ENABLED);

        // Check is valid proxy is available for the account
        if ($Account->get("proxy") && isValidProxy($Account->get("proxy"))) {
            $Instagram->setProxy($Account->get("proxy"));
        }

        // Set user
        try {
            $Instagram->setUser($Account->get("username"), $password);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }


        $last_login_timestamp = strtotime($Account->get("last_login"));
        if ($last_login_timestamp && $last_login_timestamp + 15 * 60 > time()) {
            // Recent login, there is no need to re-login
            return $Instagram;
        }



        // Login to instagram
        try {
            $Instagram->login();
        } catch (\Exception $e) {
            // Couldn't login to Instagram account
            // Skip detailed login exceptions and throw re-login 
            // required exceptions
            $Account->set("login_required", 1)->update();
            throw new Exception(__("Re-login required for %s", $Account->get("username")));
        }

        // Logged in successfully
        $Account->set("last_login", date("Y-m-d H:i:s"))->update();
        return $Instagram;
    }
}
