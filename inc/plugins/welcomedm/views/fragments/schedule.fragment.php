<?php if (!defined('APP_VERSION')) die("Yo, what's up?"); ?>

<div class="skeleton skeleton--full" id="welcomedm-schedule">
    <div class="clearfix">
        <aside class="skeleton-aside hide-on-medium-and-down">
            <div class="aside-list js-loadmore-content" data-loadmore-id="1"></div>

            <div class="loadmore pt-20 none">
                <a class="fluid button button--light-outline js-loadmore-btn js-autoloadmore-btn" data-loadmore-id="1" href="<?= APPURL."/e/".$idname."?aid=".$Account->get("id") ?>">
                    <span class="icon sli sli-refresh"></span>
                    <?= __("Load More") ?>
                </a>
            </div>
        </aside>

        <section class="skeleton-content">
            <form class="js-welcomedm-schedule-form"
                  action="<?= APPURL."/e/".$idname."/".$Account->get("id") ?>"
                  method="POST">

                <input type="hidden" name="action" value="save">

                <div class="section-header clearfix">
                    <h2 class="section-title"><?= htmlchars($Account->get("username")) ?></h2>
                </div>

                <div class="wdm-tab-heads clearfix">
                    <a href="javascript:void(0)" class="active" data-id="settings"><?= __("Settings") ?></a>
                    <a href="javascript:void(0)" data-id="messages"><?= __("Messages") ?></a>
                </div>

                <div class="section-content">
                    <div class="form-result mb-25"></div>

                    <div class="clearfix">
                        <div class="col s12 m10 l8">
                            <div class="wdm-tab-content" data-id="settings">
                                <div class="clearfix mb-40">
                                    <div class="col s6 m6 l6">
                                        <label class="form-label"><?= __("Speed") ?></label>

                                        <select class="input" name="speed">
                                            <option value="0" <?= $Schedule->get("speed") == 0 ? "selected" : "" ?>><?= __("Auto"). " (".__("Recommended").")" ?></option>
                                            <option value="1" <?= $Schedule->get("speed") == 1 ? "selected" : "" ?>><?= __("Very slow") ?></option>
                                            <option value="2" <?= $Schedule->get("speed") == 2 ? "selected" : "" ?>><?= __("Slow") ?></option>
                                            <option value="3" <?= $Schedule->get("speed") == 3 ? "selected" : "" ?>><?= __("Medium") ?></option>
                                            <option value="4" <?= $Schedule->get("speed") == 4 ? "selected" : "" ?>><?= __("Fast") ?></option>
                                            <option value="5" <?= $Schedule->get("speed") == 5 ? "selected" : "" ?>><?= __("Very Fast") ?></option>
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

                                <div class="clearfix">
                                    <div class="col s12 m6 l6">
                                        <input class="fluid button" type="submit" value="<?= __("Save") ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="wdm-tab-content none" data-id="messages">
                                <div class="mb-20">
                                    <label class="form-label"><?= __("Message") ?></label>
                                    
                                    <div class="clearfix">
                                        <div class="col s12 m12 l8">
                                            <div class="new-message-input input" 
                                                 data-placeholder="<?= __("Add your message") ?>"
                                                 contenteditable="true"></div>
                                        </div>

                                        <div class="col s12 m12 l4 l-last">
                                            <a href="javascript:void(0)" class="fluid button button--light-outline mb-15 js-add-new-message-btn">
                                                <span class="mdi mdi-plus-circle"></span>
                                                <?= __("Add Message") ?>    
                                            </a>
                                            <input class="fluid button" type="submit" value="<?= __("Save") ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="wdm-message-list clearfix">
                                    <?php 
                                        $messages = $Schedule->isAvailable()
                                                  ? json_decode($Schedule->get("messages"))
                                                  : [];
                                        $Emojione = new \Emojione\Client(new \Emojione\Ruleset());
                                    ?>
                                    <?php if ($messages): ?>
                                        <?php foreach ($messages as $m): ?>
                                            <div class="wdm-message-list-item">
                                                <a href="javascript:void(0)" class="remove-message-btn mdi mdi-close-circle"></a>
                                                <span class="message">
                                                    <?= htmlchars($Emojione->shortnameToUnicode($m)) ?>
                                                </span>
                                            </div>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
</div>