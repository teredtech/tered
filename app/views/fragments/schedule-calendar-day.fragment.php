        <div class='skeleton' id="schedule-calendar-day">
            <div class="container-1200">
                <div class="row pos-r">
                    <?php if ($Accounts->getTotalCount() > 0): ?>
                        <form class="account-selector clearfix" action="<?= APPURL."/schedule-calendar/$year/$month/$day" ?>" method="GET">
                            <span class="label"><?= __("Select Account") ?></span>

                            <select class="input input--small" name="account">
                                <?php foreach ($Accounts->getData() as $a): ?>
                                    <option value="<?= $a->id ?>" <?= $a->id == $ActiveAccount->get("id") ? "selected" : "" ?>>
                                        <?= htmlchars($a->username); ?>
                                        (<?= isset($count_per_account[$a->id]) ? $count_per_account[$a->id] : 0 ?>)
                                    </option>
                                <?php endforeach ?>
                            </select>

                            <input class="none" type="submit" value="<?= __("Submit") ?>">
                        </form>

                        <?php $Emojione = new \Emojione\Client(new \Emojione\Ruleset()); ?>
                        <div class="clearfix">
                            <div class="col s12 m6 l4 mb-20">
                                <h2 class="page-secondary-title">
                                    <?= __("In Progress") ?>
                                    <span class="badge"><?= $in_progress ?></span>
                                </h2>

                                <div class="post-list clearfix">
                                    <?php foreach ($Posts->getDataAs("Post") as $Post): ?>
                                        <?php if (!in_array($Post->get("status"), ["failed", "published"])): ?>
                                            <?php 
                                                $date = new \Moment\Moment($Post->get("schedule_date"), date_default_timezone_get());
                                                $date->setTimezone($AuthUser->get("preferences.timezone"));

                                                $dateformat = $AuthUser->get("preferences.dateformat");
                                                $timeformat = $AuthUser->get("preferences.timeformat") == "24" ? "H:i" : "h:i A";
                                                $format = $dateformat." ".$timeformat;
                                            ?>
                                            <div class="post-list-item <?= $Post->get("status") == "publishing" ? "" : "haslink" ?> js-list-item">
                                                <div>
                                                    <?php if ($Post->get("status") != "publishing"): ?>
                                                        <div class="options context-menu-wrapper">
                                                            <a href="javascript:void(0)" class="mdi mdi-dots-vertical"></a>

                                                            <div class="context-menu">
                                                                <ul>
                                                                    <li>
                                                                        <a href="<?= APPURL."/post/".$Post->get("id") ?>">
                                                                            <?= __("Edit") ?>
                                                                        </a>
                                                                    </li>

                                                                    <li>
                                                                        <a href="javascript:void(0)" 
                                                                           class="js-remove-list-item" 
                                                                           data-id="<?= $Post->get("id") ?>" 
                                                                           data-url="<?= APPURL."/schedule-calendar" ?>">
                                                                            <?= __("Delete") ?>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    <?php endif ?>

                                                    <div class="quick-info">
                                                        <?php if ($Post->get("status") == "publishing"): ?>
                                                            <span class="color-dark">
                                                                <span class="icon sli sli-energy"></span>
                                                                <?= __("Processing now...") ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?php 
                                                                $diff = $date->fromNow(); 

                                                                if ($diff->getDirection() == "future") {
                                                                    echo $diff->getRelative();
                                                                } else if (abs($diff->getSeconds()) < 60*10) {
                                                                    echo __("In a few moments");
                                                                } else {
                                                                    echo __("System task error");
                                                                }
                                                            ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="cover">
                                                        <?php 
                                                            $media_ids = explode(",", $Post->get("media_ids"));
                                                            $File = Controller::model("File", $media_ids[0]);

                                                            $type = null;
                                                            if ($File->isAvailable()) {
                                                                $ext = strtolower(pathinfo($File->get("filename"), PATHINFO_EXTENSION));

                                                                if (in_array($ext, ["mp4"])) {
                                                                    $type = "video";                                                                
                                                                } else if (in_array($ext, ["jpg", "jpeg", "png"])) {
                                                                    $type = "image";
                                                                }
                                                            }

                                                            $fileurl = APPURL
                                                                     . "/assets/uploads/" 
                                                                     . $AuthUser->get("id") 
                                                                     . "/" . $File->get("filename");

                                                            $filepath = ROOTPATH
                                                                      . "/assets/uploads/" 
                                                                      . $AuthUser->get("id") 
                                                                      . "/" . $File->get("filename");
                                                        ?>
                                                        <?php if (file_exists($filepath)): ?>
                                                            <?php if ($type == "image"): ?>
                                                                <div class="img" style="background-image: url('<?= $fileurl ?>')"></div>
                                                            <?php else: ?>
                                                                <video src='<?= $fileurl ?>' playsinline autoplay muted loop></video>
                                                            <?php endif ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="caption">
                                                        <?= truncate_string($Emojione->shortnameToUnicode($Post->get("caption")), 50); ?>
                                                    </div>

                                                    <div class="quick-info mb-10">
                                                        <?php if ($Post->get("type") == "album"): ?>
                                                            <span class="icon sli sli-layers"></span>
                                                            <?= __("Album") ?>
                                                        <?php elseif ($Post->get("type") == "story"): ?>
                                                            <span class="icon sli sli-plus"></span>
                                                            <?= __("Story") ?>
                                                        <?php else: ?>
                                                            <span class="icon sli sli-camera"></span>
                                                            <?= __("Regular Post") ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="quick-info">
                                                        <span class="icon sli sli-calendar"></span>
                                                        <?= $date->format($format); ?>
                                                    </div>

                                                    <?php if ($Post->get("status") == "scheduled"): ?>
                                                        <a class="full-link" href="<?= APPURL."/post/".$Post->get("id") ?>"></a>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </div>
                            </div>

                            <div class="col s12 m6 m-last l4">
                                <h2 class="page-secondary-title">
                                    <?= __("Completed") ?>
                                    <span class="badge"><?= $completed ?></span>
                                </h2>

                                <div class="post-list clearfix">
                                    <?php foreach ($Posts->getDataAs("Post") as $Post): ?>
                                        <?php if (in_array($Post->get("status"), ["failed", "published"])): ?>
                                            <?php 
                                                $date = new \Moment\Moment($Post->get("schedule_date"), date_default_timezone_get());
                                                $date->setTimezone($AuthUser->get("preferences.timezone"));

                                                $dateformat = $AuthUser->get("preferences.dateformat");
                                                $timeformat = $AuthUser->get("preferences.timeformat") == "24" ? "H:i" : "h:i A";
                                                $format = $dateformat." ".$timeformat;
                                            ?>
                                            <div class="post-list-item haslink js-list-item">
                                                <div>
                                                    <div class="options context-menu-wrapper">
                                                        <a href="javascript:void(0)" class="mdi mdi-dots-vertical"></a>

                                                        <div class="context-menu">
                                                            <ul>
                                                                <?php if ($Post->get("status") == "published"): ?>
                                                                    <li>
                                                                        <a href="<?= "https://www.instagram.com/p/".$Post->get("data.code") ?>" target="_blank">
                                                                            <?= __("View on Instagram") ?>
                                                                        </a>
                                                                    </li>
                                                                <?php else: ?>
                                                                    <li>
                                                                        <a href="<?= APPURL."/post/".$Post->get("id") ?>">
                                                                            <?= __("Edit") ?>
                                                                        </a>
                                                                    </li>

                                                                    <li>
                                                                        <a href="javascript:void(0)" 
                                                                           class="js-remove-list-item" 
                                                                           data-id="<?= $Post->get("id") ?>" 
                                                                           data-url="<?= APPURL."/schedule-calendar" ?>">
                                                                            <?= __("Delete") ?>
                                                                        </a>
                                                                    </li>
                                                                <?php endif ?>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    <div class="quick-info">
                                                        <?php if ($Post->get("status") == "published"): ?>
                                                            <span class="color-success">
                                                                <span class="icon sli sli-check"></span>
                                                                <?= __("Published") ?>
                                                                <?php else: ?>
                                                            </span>
                                                            <span class="color-danger">
                                                                <span class="icon sli sli-close"></span>
                                                                <?= __("Failed") ?>
                                                                <?php endif ?>
                                                            </span>
                                                    </div>

                                                    <div class="cover">
                                                        <?php 
                                                            $media_ids = explode(",", $Post->get("media_ids"));
                                                            $File = Controller::model("File", $media_ids[0]);

                                                            $type = null;
                                                            if ($File->isAvailable()) {
                                                                $ext = strtolower(pathinfo($File->get("filename"), PATHINFO_EXTENSION));

                                                                if (in_array($ext, ["mp4"])) {
                                                                    $type = "video";                                                                
                                                                } else if (in_array($ext, ["jpg", "jpeg", "png"])) {
                                                                    $type = "image";
                                                                }
                                                            }

                                                            $fileurl = APPURL
                                                                     . "/assets/uploads/" 
                                                                     . $AuthUser->get("id") 
                                                                     . "/" . $File->get("filename");

                                                            $filepath = ROOTPATH
                                                                      . "/assets/uploads/" 
                                                                      . $AuthUser->get("id") 
                                                                      . "/" . $File->get("filename");
                                                        ?>
                                                        <?php if (file_exists($filepath)): ?>
                                                            <?php if ($type == "image"): ?>
                                                                <div class="img" style="background-image: url('<?= $fileurl ?>')"></div>
                                                            <?php else: ?>
                                                                <video src='<?= $fileurl ?>' playsinline autoplay muted loop></video>
                                                            <?php endif ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="caption">
                                                        <?= truncate_string($Emojione->shortnameToUnicode($Post->get("caption")), 50); ?>
                                                    </div>

                                                    <div class="quick-info mb-10">
                                                        <?php if ($Post->get("type") == "album"): ?>
                                                            <span class="icon sli sli-layers"></span>
                                                            <?= __("Album") ?>
                                                        <?php elseif ($Post->get("type") == "story"): ?>
                                                            <span class="icon sli sli-plus"></span>
                                                            <?= __("Story") ?>
                                                        <?php else: ?>
                                                            <span class="icon sli sli-camera"></span>
                                                            <?= __("Regular Post") ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <div class="quick-info">
                                                        <span class="icon sli sli-calendar"></span>
                                                        <?= $date->format($format); ?>
                                                    </div>

                                                    <?php if ($Post->get("status") == "published"): ?>
                                                        <a class="full-link" href="<?= "https://www.instagram.com/p/".$Post->get("data.code") ?>" target="_blank"></a>
                                                    <?php else: ?>
                                                        <a class="full-link" href="<?= APPURL."/post/".$Post->get("id") ?>"></a>
                                                    <?php endif ?>
                                                </div>
                                            </div>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <?php if ($AuthUser->get("settings.max_accounts") == -1 || $AuthUser->get("settings.max_accounts") > 0): ?>
                                <p><?= __("You haven't add any Instagram account yet. Click the button below to add your first account.") ?></p>
                                <a class="small button" href="<?= APPURL."/accounts/new" ?>">
                                    <span class="sli sli-user-follow"></span>
                                    <?= __("New Account") ?>
                                </a>
                            <?php else: ?>
                                <p><?= __("You don't have any Instagram account.") ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif ?>

                </div>
            </div>
        </div>