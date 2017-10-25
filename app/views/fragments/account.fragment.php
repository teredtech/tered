        <div class='skeleton' id="account">
            <form class="js-ajax-form" 
                  action="<?= APPURL . "/accounts/" . ($Account->isAvailable() ? $Account->get("id") : "new") ?>"
                  method="POST">
                <input type="hidden" name="action" value="save">

                <div class="container-1200">
                    <div class="row clearfix">
                        <div class="col s12 m8 l4">
                            <section class="section">
                                <div class="section-content">
                                    <div class="form-result">
                                    </div>

                                    <div class="mb-20">
                                        <label class="form-label">
                                            <?= __("Username") ?>
                                            <span class="compulsory-field-indicator">*</span>    
                                        </label>

                                        <input class="input js-required"
                                               name="username" 
                                               type="text" 
                                               value="<?= htmlchars($Account->get("username")) ?>" 
                                               placeholder="<?= __("Enter username") ?>">
                                    </div>

                                    <div class="">
                                        <label class="form-label">
                                            <?= __("Password") ?>
                                            <span class="compulsory-field-indicator">*</span>    
                                        </label>

                                        <input class="input js-required"
                                               name="password" 
                                               type="password" 
                                               placeholder="<?= __("Enter password") ?>">
                                    </div>

                                    <?php if ($Settings->get("data.proxy") && $Settings->get("data.user_proxy")): ?>
                                        <div class="mt-20">
                                            <label class="form-label"><?= __("Proxy") ?> (<?= ("Optional") ?>)</label>

                                            <input class="input"
                                                   name="proxy" 
                                                   type="text" 
                                                   value="<?= htmlchars($Account->get("proxy")) ?>" 
                                                   placeholder="<?= __("Proxy for your country") ?>">
                                        </div>

                                        <ul class="field-tips">
                                            <li><?= __("Proxy should match following pattern: http://ip:port OR http://username:password@ip:port") ?></li>
                                            <li><?= __("It's recommended to to use a proxy belongs to the country where you've logged in this acount in Instagram's official app or website.") ?></li>
                                        </ul>
                                    <?php endif ?>
                                </div>

                                <input class="fluid button button--footer" type="submit" value="<?= $Account->isAvailable() ? __("Save changes") :  __("Add account") ?>">
                            </section>
                        </div>
                    </div>
                </div>
            </form>
        </div>