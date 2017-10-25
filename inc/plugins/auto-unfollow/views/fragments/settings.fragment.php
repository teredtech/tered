<?php if (!defined('APP_VERSION')) die("Yo, what's up?"); ?>

<div class='skeleton' id="account">
    <form class="js-ajax-form" 
          action="<?= APPURL . "/e/" . $idname . "/settings" ?>"
          method="POST">
        <input type="hidden" name="action" value="save">

        <div class="container-1200">
            <div class="row clearfix">
                <div class="form-result">
                </div>

                <div class="col s12 m8 l4">
                    <section class="section">
                        <div class="section-header clearfix hide-on-small-only">
                            <h2 class="section-title"><?= __("Speeds") ?></h2>
                        </div>

                        <div class="section-content">
                            <div class="mb-10 clearfix">
                                <div class="col s6 m6 l6">
                                    <label class="form-label"><?= __("Very Slow") ?></label>

                                    <select name="speed-very-slow" class="input">
                                        <?php $s = $Settings->get("data.speeds.very_slow") ?>
                                        <?php for ($i=1; $i<=60; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $s ? "selected" : "" ?>>
                                                <?= n__("%s request/hour", "%s requests/hour", $i, $i) ?>                                                    
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col s6 s-last m6 m-last l6 l-last mb-20">
                                    <label class="form-label"><?= __("Slow") ?></label>

                                    <select name="speed-slow" class="input">
                                        <?php $s = $Settings->get("data.speeds.slow") ?>
                                        <?php for ($i=1; $i<=60; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $s ? "selected" : "" ?>>
                                                <?= n__("%s request/hour", "%s requests/hour", $i, $i) ?>                                                    
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-10 clearfix">
                                <div class="col s6 m6 l6">
                                    <label class="form-label"><?= __("Medium") ?></label>

                                    <select name="speed-medium" class="input">
                                        <?php $s = $Settings->get("data.speeds.medium") ?>
                                        <?php for ($i=1; $i<=60; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $s ? "selected" : "" ?>>
                                                <?= n__("%s request/hour", "%s requests/hour", $i, $i) ?>                                                    
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col s6 s-last m6 m-last l6 l-last mb-20">
                                    <label class="form-label"><?= __("Fast") ?></label>

                                    <select name="speed-fast" class="input">
                                        <?php $s = $Settings->get("data.speeds.fast") ?>
                                        <?php for ($i=1; $i<=60; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $s ? "selected" : "" ?>>
                                                <?= n__("%s request/hour", "%s requests/hour", $i, $i) ?>                                                    
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-30 clearfix">
                                <div class="col s6 m6 l6">
                                    <label class="form-label"><?= __("Very Fast") ?></label>

                                    <select name="speed-very-fast" class="input">
                                        <?php $s = $Settings->get("data.speeds.very_fast") ?>
                                        <?php for ($i=1; $i<=60; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $s ? "selected" : "" ?>>
                                                <?= n__("%s request/hour", "%s requests/hour", $i, $i) ?>                                                    
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <ul class="field-tips">
                                <li><?= __("These values indicates maximum amount of the requests per hour. They are not exact values. Depending on the server overload and delays between the requests, actual number of the requests might be less than these values.") ?></li>
                                <li><?= __("High speeds might be risky") ?></li>
                                <li><?= __("Developers are not responsible for any issues related to the Instagram accounts.") ?></li>
                            </ul>
                        </div>
                    </section>
                </div>

                <div class="col s12 m8 l4">
                    <section class="section">
                        <div class="section-header clearfix hide-on-small-only">
                            <h2 class="section-title"><?= __("Other Settings") ?></h2>
                        </div>

                        <div class="section-content">
                            <div class="mb-20">
                                <label>
                                    <input type="checkbox" 
                                           class="checkbox" 
                                           name="random_delay" 
                                           value="1" 
                                           <?= $Settings->get("data.random_delay") ? "checked" : "" ?>>
                                    <span>
                                        <span class="icon unchecked">
                                            <span class="mdi mdi-check"></span>
                                        </span>
                                        <?= __('Enable Random Delays') ?>
                                        (<?= __("Recommended") ?>)

                                        <ul class="field-tips">
                                            <li><?= __("If you enable this option, script will add random delays automatically between each requests.") ?></li>
                                            <li><?= __("Delays could be up to 5 minutes.") ?></li>
                                        </ul>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <input class="fluid button button--footer" type="submit" value="<?= __("Save") ?>">
                    </section>
                </div>
            </div>
        </div>
    </form>
</div>