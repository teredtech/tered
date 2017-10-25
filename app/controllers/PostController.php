<?php
/**
 * Post Controller
 */
class PostController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        // Auth
        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }

        // Identify post
        if (isset($Route->params->id)) {
            $Post = Controller::model("Post", $Route->params->id);
            $allowed_statuses = ["scheduled", "failed"];

            if (!$Post->isAvailable() || // Post is not available
                !in_array($Post->get("status"), $allowed_statuses) ||  // Post is already published or processing now
                $Post->get("user_id") != $AuthUser->get("id")
            ) {
                header("Location: ".APPURL."/post");
                exit;
            }
        } else {
            $Post = Controller::model("Post");
        }

        // Get my accounts
        $Accounts = Controller::model("Accounts");
            $Accounts->where("user_id", "=", $AuthUser->get("id"))
                     ->where("login_required", "=", 0)
                     ->orderBy("username","ASC")
                     ->fetchData();
        
        $this->setVariable("Post", $Post)
             ->setVariable("Accounts", $Accounts)
             ->setVariable("isVideoExtenstionsLoaded", isVideoExtenstionsLoaded())
             ->setVariable("Integrations", Controller::model("GeneralData", "integrations"));

        if (Input::post("action") == "post") {
            $this->post();
        }
        $this->view("post");
    }


    /**
     * Publish or Schedule post
     * @return void 
     */
    private function post()
    {
        $this->resp->result = 0;

        $AuthUser = $this->getVariable("AuthUser");
        $Post = $this->getVariable("Post");
        $is_new = !$Post->isAvailable();
        $isVideoExtenstionsLoaded = $this->getVariable("isVideoExtenstionsLoaded");
        $Accounts = $this->getVariable("Accounts");

        // Emojione Client
        $Emojione = new \Emojione\Client(new \Emojione\Ruleset());



        // Ckeck post type
        $type = Input::post("type");
        if (!in_array($type, ["timeline", "story", "album"])) {
            $type = "timeline";
        }



        // Check media ids
        $media_ids = explode(",", Input::post("media_ids"));
        foreach ($media_ids as $i => $id) {
            if ((int)$id < 1) {
                unset($media_ids[$i]);
            } else {
                $id = (int)$id;
            }
        }

        $query = DB::table(TABLE_PREFIX.TABLE_FILES)
               ->where("user_id", "=", $AuthUser->get("id"))
               ->whereIn("id", $media_ids);
        $res = $query->get();

        $valid_media_ids = [];
        $media_data = [];
        foreach ($res as $m) {
            $valid_media_ids[] = $m->id;
            $media_data[$m->id] = $m;
        }

        foreach ($media_ids as $i => $id) {
            if (!in_array($id, $valid_media_ids)) {
                unset($media_ids[$i]);
            }
        }

        if ($type == "album" && count($media_ids) < 2) {
            $this->resp->msg = __("Please select at least 2 media this album post.");
            $this->jsonecho();
        } else if ($type == "story" && count($media_ids) < 1) {
            $this->resp->msg = __("Please select one media for this story post.");
            $this->jsonecho();
        } else if ($type == "timeline" && count($media_ids) < 1) {
            $this->resp->msg = __("Please select one media for this post.");
            $this->jsonecho();
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



        // Check caption
        $caption = Input::post("caption");
        $caption = $Emojione->shortnameToUnicode($caption);
        $caption = mb_substr($caption, 0, 2200);
        $caption = $Emojione->toShort($caption);



        // Check accounts
        $account_ids = Input::post("accounts");
        if (!is_array($account_ids)) {
            $account_ids = array($account_ids);
        }

        $all_account_ids = [];
        $account_data = [];
        foreach ($Accounts->getDataAs("Account") as $a) {
            $all_account_ids[] = $a->get("id");
            $account_data[$a->get("id")] = $a;
        }

        foreach ($account_ids as $i => $id) {
            if (!in_array($id, $all_account_ids)) {
                unset($account_ids[$i]);
            }
        }

        if (!$account_ids) {
            $this->resp->msg = __("Please select at least one Instagram account.");
            $this->jsonecho();
        }
        


        // Check schedule
        $is_scheduled = (bool)Input::post("is_scheduled");
        $user_datetime_format = Input::post("user_datetime_format");
        if (!$user_datetime_format) {
            $user_datetime_format = $AuthUser->get("preferences.dateformat") 
                                  . " "
                                  . ($AuthUser->get("preferences.timeformat") == "24" ? 
                                     "H:i" : "h:i A");
        }

        $timezone = $AuthUser->get("preferences.timezone");
        $schedule_date = Input::post("schedule_date");
        if ($is_scheduled) {
            if (isValidDate($schedule_date, $user_datetime_format)) {
                $schedule_date = \DateTime::createFromFormat($user_datetime_format, $schedule_date, new DateTimeZone($timezone));
                $schedule_date->setTimezone(new DateTimeZone("UTC"));
            } else {
                $is_scheduled = false;
            }
        }


        // Define status
        $status = $is_scheduled ? "scheduled" : "publishing";


        // Check permissions
        foreach ($media_ids as $id) {
            $media = $media_data[$id];
            $ext = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));

            if (in_array($ext, ["mp4"])) {
                if (!$isVideoExtenstionsLoaded) {
                    $this->resp->msg = __("It's not possible to post video files right now!");
                    $this->jsonecho();    
                }

                $permission = "settings.post_types.".$type."_video";
            } else if (in_array($ext, ["jpg", "jpeg", "png"])) {
                $permission = "settings.post_types.".$type."_photo";
            } else {
                $this->resp->msg = __("Oops! An error occured. Please try again later!");
                $this->jsonecho();
            }

            if (!$AuthUser->get($permission)) {
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

                $this->resp->msg = $msg;
                $this->jsonecho();
            }
        }


        // If post exists, get create date and remove it
        // It will be created again as a new post
        if ($is_new) {
            $create_date = date("Y-m-d H:i:s");
        } else {
            $create_date = $Post->get("create_date");
            $old_post_id = $Post->get("id");
            $Post->remove();
        }


        $posts = [];
        foreach ($account_ids as $aid) {

            // Create new post
            $Post = Controller::model("Post");
            $Post->set("status", $status)
                 ->set("user_id", $AuthUser->get("id"))
                 ->set("type", $type)
                 ->set("caption", $caption)
                 ->set("media_ids", implode(",", $media_ids))
                 ->set("account_id", $aid)
                 ->set("is_scheduled", $is_scheduled)
                 ->set("create_date", $create_date);


            if ($is_scheduled) {
                $Post->set("schedule_date", $schedule_date->format("Y-m-d H:i:s"));
            } else {
                $Post->set("schedule_date", date("Y-m-d H:i:s"));
            }
            
            $Post->save();

            $posts[] = $Post;
        }
        


        if ($status == "scheduled") {
            $date = new Moment\Moment($Post->get("schedule_date"), date_default_timezone_get());
            $date->setTimezone($AuthUser->get("preferences.timezone"));
            $format = $AuthUser->get("preferences.dateformat") . " "
                    . ($AuthUser->get("preferences.timeformat") == "24" ? "H:i" : "h:i A");

            $this->resp->result = 1;
            if ($is_new) {
                $this->resp->msg = __("Post has been scheduled to %s", $date->format($format));
            } else {
                $this->resp->msg = __("Post has been re-scheduled to %s", $date->format($format));
            }
            $this->jsonecho();
        } else {
            // Publish posts to Instagram
            $results = [
                "success" => [],
                "fail" => []
            ];


            foreach ($posts as $Post) {
                try {
                    $ig_media_code = InstagramController::publish($Post);

                    $results["success"][] = [
                        "account_id" => $Post->get("account_id"),
                        "username" => $account_data[$Post->get("account_id")]->get("username"),
                        "url" => "https://www.instagram.com/p/".$ig_media_code
                    ];
                } catch (\Exception $e) {
                    $results["fail"][] = [
                        "account_id" => $Post->get("account_id"),
                        "username" => $account_data[$Post->get("account_id")]->get("username"),
                        "url" => APPURL."/post/".$Post->get("id"),
                        "msg" => $e->getMessage()
                    ];
                }
            }

            if (!empty($results["success"]) && empty($results["fail"])) {
                // Published all posts
                $this->resp->result = 1;
                if (count($posts) > 1) {
                    $this->resp->msg = __("Post published successfully! Click on the usernames to view the published posts.");
                    $this->resp->details = [];
                    foreach ($results["success"] as $r) {
                        $r["type"] = "success";
                        $this->resp->details[] = $r;
                    }
                } else {
                    $this->resp->msg = __("Post published successfully! <a href='%s'>View post</a>", 
                                          $results["success"][0]["url"]);
                }
            } else if (!empty($results["fail"]) && empty($results["success"])) {
                // Failed for all posts
                $this->resp->result = -1;

                $this->resp->details = [];
                foreach ($results["fail"] as $r) {
                    $r["type"] = "fail";
                    $this->resp->details[] = $r;
                }
                
                if (count($posts) > 1) {
                    $this->resp->msg = __("Failed to publish the post! Click on the usernames to view the failed posts.");
                } else {
                    // There is only one most, remove it
                    // There no need to keep it as a failed post
                    if (count($posts) == 1) {
                        if ($is_new) {
                            $posts[0]->remove();
                        } else {
                            $posts[0]->updateId($old_post_id);
                        }
                    }
                    $this->resp->msg = $results["fail"][0]["msg"];
                }
            } else {
                // Published for some and failed for rest
                // Number of posts is definitely bigger than one
                $this->resp->result = 2;
                $this->resp->msg = __("Post successfully published only for some accounts.");
                $this->resp->details = [];
                
                foreach ($results["success"] as $r) {
                    $r["type"] = "success";
                    $this->resp->details[] = $r;
                }

                foreach ($results["fail"] as $r) {
                    $r["type"] = "fail";
                    $this->resp->details[] = $r;
                }
            }


            $this->jsonecho();
        }
    }
}
