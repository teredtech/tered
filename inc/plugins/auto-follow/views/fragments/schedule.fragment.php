<?php if (!defined('APP_VERSION')) die("Yo, what's up?"); ?>

<div class="skeleton skeleton--full">
    <div class="clearfix">
        <aside class="skeleton-aside hide-on-medium-and-down">
            <div class="aside-list js-loadmore-content" data-loadmore-id="1"></div>

            <div class="loadmore pt-20 mb-20 none">
                <a class="fluid button button--light-outline js-loadmore-btn js-autoloadmore-btn" data-loadmore-id="1" href="<?= APPURL."/e/".$idname."?aid=".$Account->get("id")."&ref=schedule" ?>">
                    <span class="icon sli sli-refresh"></span>
                    <?= __("Load More") ?>
                </a>
            </div>
        </aside>

        <section class="skeleton-content">
            <form class="js-auto-follow-schedule-form"
                  action="<?= APPURL."/e/".$idname."/".$Account->get("id") ?>"
                  method="POST">

                <input type="hidden" name="action" value="save">

                <div class="section-header clearfix">
                    <h2 class="section-title">
                        <?= htmlchars($Account->get("username")) ?>
                        <?php if ($Account->get("login_required")): ?>
                            <small class="color-danger ml-15">
                                <span class="mdi mdi-information"></span>    
                                <?= __("Re-login required!") ?>
                            </small>
                        <?php endif ?>
                    </h2>
                </div>

                <div class="af-tab-heads clearfix">
                    <a href="<?= APPURL."/e/".$idname."/".$Account->get("id") ?>" class="active"><?= __("Target & Settings") ?></a>
                    <a href="<?= APPURL."/e/".$idname."/".$Account->get("id")."/log" ?>"><?= __("Activity Log") ?></a>
                </div>

                <div class="section-content">
                    <div class="form-result mb-25" style="display:none;"></div>

                    <div class="clearfix">
                        <div class="col s12 m12 l8">
                            <div class="mb-5 clearfix">
                                <label class="inline-block mr-50 mb-15">
                                    <input class="radio" name='type' type="radio" value="hashtag" checked>
                                    <span>
                                        <span class="icon"></span>
                                        #<?= __("Hashtags") ?>
                                    </span>
                                </label>

                                <label class="inline-block mr-50 mb-15">
                                    <input class="radio" name='type' type="radio" value="location">
                                    <span>
                                        <span class="icon"></span>
                                        <?= __("Places") ?>
                                    </span>
                                </label>

                                <label class="inline-block mb-15">
                                    <input class="radio" name='type' type="radio" value="people">
                                    <span>
                                        <span class="icon"></span>
                                        <?= __("People") ?>
                                    </span>
                                </label>
                            </div>

                            <div class="clearfix mb-20 pos-r">
                                <label class="form-label"><?= __('Search') ?></label>
                                <input class="input rightpad" name="search" type="text" value="" 
                                       data-url="<?= APPURL."/e/".$idname."/".$Account->get("id") ?>"
                                       <?= $Account->get("login_required") ? "disabled" : "" ?>>
                                <span class="field-icon--right pe-none none js-search-loading-icon">
                                    <img src="<?= APPURL."/assets/img/round-loading.svg" ?>" alt="Loading icon">
                                </span>
                            </div>

                            <div class="tags clearfix mt-20 mb-20">
                                <?php 
                                    $targets = $Schedule->isAvailable()
                                             ? json_decode($Schedule->get("target")) 
                                             : []; 
                                    $icons = [
                                        "hashtag" => "mdi mdi-pound",
                                        "location" => "mdi mdi-map-marker",
                                        "people" => "mdi mdi-instagram"
                                    ];
                                ?>
                                <?php foreach ($targets as $t): ?>
                                    <span class="tag pull-left"
                                          data-type="<?= htmlchars($t->type) ?>" 
                                          data-id="<?= htmlchars($t->id) ?>" 
                                          data-value="<?= htmlchars($t->value) ?>" 
                                          style="margin: 0px 2px 3px 0px;">
                                        <?php if (isset($icons[$t->type])): ?>
                                              <span class="<?= $icons[$t->type] ?>"></span>
                                          <?php endif ?>  

                                          <?= htmlchars($t->value) ?>
                                          <span class="mdi mdi-close remove"></span>
                                      </span>
                                <?php endforeach ?>
                            </div>

                            <div class="clearfix mb-20">
                                <div class="col s6 m6 l6">
                                    <label class="form-label"><?= __("Speed") ?></label>

                                    <select class="input" name="speed">
                                        <option value="auto" <?= $Schedule->get("speed") == "auto" ? "selected" : "" ?>><?= __("Auto"). " (".__("Recommended").")" ?></option>
                                        <option value="very_slow" <?= $Schedule->get("speed") == "very_slow" ? "selected" : "" ?>><?= __("Very Slow") ?></option>
                                        <option value="slow" <?= $Schedule->get("speed") == "slow" ? "selected" : "" ?>><?= __("Slow") ?></option>
                                        <option value="medium" <?= $Schedule->get("speed") == "medium" ? "selected" : "" ?>><?= __("Medium") ?></option>
                                        <option value="fast" <?= $Schedule->get("speed") == "fast" ? "selected" : "" ?>><?= __("Fast") ?></option>
                                        <option value="very_fast" <?= $Schedule->get("speed") == "very_fast" ? "selected" : "" ?>><?= __("Very Fast") ?></option>
                                    </select>
                                </div>

                                <div class="col s6 s-last m6 m-last l6 l-last">
                                    <label class="form-label"><?= __("Status") ?></label>

                                    <select class="input" name="is_active">
                                        <option value="0" <?= $Schedule->get("is_active") == 0 ? "selected" : "" ?>><?= __("Deactive") ?></option>
                                        <option value="1" <?= $Schedule->get("is_active") == 1 ? "selected" : "" ?>><?= __("Active") ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-40 mt-40">
                                <div class="mb-20">
                                    <label>
                                        <input type="checkbox" 
                                               class="checkbox" 
                                               name="daily-pause" 
                                               value="1"
                                               <?= $Schedule->get("daily_pause") ? "checked" : "" ?>>
                                        <span>
                                            <span class="icon unchecked">
                                                <span class="mdi mdi-check"></span>
                                            </span>
                                            <?= __('Pause actions everyday') ?> ...
                                        </span>
                                    </label>
                                </div>

                                <div class="clearfix js-daily-pause-range">
                                    <?php $timeformat = $AuthUser->get("preferences.timeformat") == "12" ? 12 : 24; ?>

                                    <div class="col s6 m3 l3">
                                        <label class="form-label"><?= __("From") ?></label>

                                        <?php 
                                            $from = new \DateTime(date("Y-m-d")." ".$Schedule->get("daily_pause_from"));
                                            $from->setTimezone(new \DateTimeZone($AuthUser->get("preferences.timezone")));
                                            $from = $from->format("H:i");
                                        ?>

                                        <select class="input" name="daily-pause-from">
                                            <?php for ($i=0; $i<=23; $i++): ?>
                                                <?php $time = str_pad($i, 2, "0", STR_PAD_LEFT).":00"; ?>
                                                <option value="<?= $time ?>" <?= $from == $time ? "selected" : "" ?>>
                                                    <?= $timeformat == 24 ? $time : date("h:iA", strtotime(date("Y-m-d")." ".$time)) ?>    
                                                </option>
                                                
                                                <?php $time = str_pad($i, 2, "0", STR_PAD_LEFT).":30"; ?>
                                                <option value="<?= $time ?>" <?= $from == $time ? "selected" : "" ?>>
                                                    <?= $timeformat == 24 ? $time : date("h:iA", strtotime(date("Y-m-d")." ".$time)) ?>    
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="col s6 s-last m3 m-last l3 l-last">
                                        <label class="form-label"><?= __("To") ?></label>

                                        <?php 
                                            $to = new \DateTime(date("Y-m-d")." ".$Schedule->get("daily_pause_to"));
                                            $to->setTimezone(new \DateTimeZone($AuthUser->get("preferences.timezone")));
                                            $to = $to->format("H:i");
                                        ?>

                                        <select class="input" name="daily-pause-to">
                                            <?php for ($i=0; $i<=23; $i++): ?>
                                                <?php $time = str_pad($i, 2, "0", STR_PAD_LEFT).":00"; ?>
                                                <option value="<?= $time ?>" <?= $to == $time ? "selected" : "" ?>>
                                                    <?= $timeformat == 24 ? $time : date("h:iA", strtotime(date("Y-m-d")." ".$time)) ?>    
                                                </option>
                                                
                                                <?php $time = str_pad($i, 2, "0", STR_PAD_LEFT).":30"; ?>
                                                <option value="<?= $time ?>" <?= $to == $time ? "selected" : "" ?>>
                                                    <?= $timeformat == 24 ? $time : date("h:iA", strtotime(date("Y-m-d")." ".$time)) ?>    
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix">
                                <div class="col s12 m6 l6">
                                    <input class="fluid button" type="submit" value="<?= __("Save") ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
</div>