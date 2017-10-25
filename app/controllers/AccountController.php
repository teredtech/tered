<?php
/**
 * Account Controller
 */
class AccountController extends Controller
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


        // Get accounts
        $Accounts = Controller::model("Accounts");
            $Accounts->setPage(Input::get("page"))
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->fetchData();

        // Account
        if (isset($Route->params->id)) {
            $Account = Controller::model("Account", $Route->params->id);
            if (!$Account->isAvailable() || 
                $Account->get("user_id") != $AuthUser->get("id")) 
            {
                header("Location: ".APPURL."/accounts");
                exit;
            }
        } else {
            $max_accounts = $AuthUser->get("settings.max_accounts");
            if ($Accounts->getTotalCount() >= $max_accounts && $max_accounts != "-1") {
                // Max. limit exceeds
                header("Location: ".APPURL."/accounts");
                exit;
            }

            $Account = Controller::model("Account"); // new account model
        }


        // Set view variables
        $this->setVariable("Accounts", $Accounts)
             ->setVariable("Account", $Account)
             ->setVariable("Settings", Controller::model("GeneralData", "settings"));


        if (Input::post("action") == "save") {
            $this->save();
        }
        $this->view("account");
    }


    /**
     * Save (new|edit)
     * @return void 
     */
    private function save()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Account = $this->getVariable("Account");
        $Settings = $this->getVariable("Settings");
        $IpInfo = $this->getVariable("IpInfo");

        $username = Input::post("username");
        $password = Input::post("password");


        // Check required data
        if (!$username || !$password) {
            $this->resp->msg = __("Missing some of required data.");
            $this->jsonecho();
        }

        
        // Check username
        $username_is_exists = false;
        $check_username = true;
        if ($Account->isAvailable() && $Account->get("username") == $username) {
            $check_username = false;
        }

        if ($check_username) {
            foreach ($this->getVariable("Accounts")->getData() as $a) {
                if ($a->username == strtolower(Input::post("username"))) {

                    $username_is_exists = true;
                    break;
                }
            }

            if ($username_is_exists) {
                $this->resp->msg = __("Account is already exists!");
                $this->jsonecho();
            }
        }


        // Check proxy
        $proxy = null;
        $is_system_proxy = false;
        if ($Settings->get("data.proxy")) {
            if (Input::post("proxy") && $Settings->get("data.user_proxy")) {
                $proxy = Input::post("proxy");

                if (!isValidProxy($proxy)) {
                    $this->resp->msg = __("Proxy is not valid or active!");
                    $this->jsonecho();
                }
            } else {
                $user_country = !empty($IpInfo->countryCode) 
                              ? $IpInfo->countryCode : null;
                $countries = [];
                if (!empty($IpInfo->neighbours)) {
                    $countries = $IpInfo->neighbours;
                }
                array_unshift($countries, $user_country);
                $proxy = ProxiesModel::getBestProxy($countries);
                $is_system_proxy = true;
            }
        }


        if ($Account->isAvailable()) {
            // Data changed, set as logged out for now
            $Account->set("login_required", 1)->save();
        }
        

        $storageConfig = [
            "storage" => "file",
            "basefolder" => SESSIONS_PATH."/".$AuthUser->get("id")."/",
        ];

        $Instagram = new \InstagramAPI\Instagram(false, false, $storageConfig);
        $Instagram->setVerifySSL(SSL_ENABLED);

        if ($proxy) {
            $Instagram->setProxy($proxy);
        }
        
        try {
            $Instagram->setUser($username, $password);
            $login_resp = $Instagram->login(true);
        } catch (InstagramAPI\Exception\SettingsException $e) {
            $this->resp->msg = $e->getMessage();
            $this->jsonecho();
        } catch (InstagramAPI\Exception\CheckpointRequiredException $e) {
            $this->resp->result = 2;
            $this->resp->msg = __("Please goto <a href='http://instagram.com' target='_blank'>instagram.com</a> and pass checkpoint!");
            $this->jsonecho();
        } catch (InstagramAPI\Exception\InstagramException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "The password you entered is incorrect") !== false) {
                $msg = __("The password you entered is incorrect. Please try again.");
            } else if (strpos($msg, "Please check your username and try again") !== false) {
                $msg = __("The username you entered doesn't appear to belong to an account. Please check your username and try again.");
            }

            $this->resp->msg = $msg;
            $this->jsonecho();
        } catch (\Exception $e) {
            $this->resp->msg = __("Oops! Something went wrong. Please try again later!");
            $this->jsonecho();
        }


        // Check if this is new or not
        $is_new = !$Account->isAvailable();

        $passhash = Defuse\Crypto\Crypto::encrypt(Input::post("password"), 
                        Defuse\Crypto\Key::loadFromAsciiSafeString(CRYPTO_KEY));

        $Account->set("user_id", $AuthUser->get("id"))
                ->set("instagram_id", $Instagram->account_id)
                ->set("username", $login_resp->logged_in_user->username)
                ->set("password", $passhash)
                ->set("proxy", $proxy ? $proxy : "")
                ->set("login_required", 0)
                ->save();


        // Update proxy use count
        if ($proxy && $is_system_proxy == true) {
            $Proxy = Controller::model("Proxy", $proxy);
            if ($Proxy->isAvailable()) {
                $Proxy->set("use_count", $Proxy->get("use_count") + 1)
                      ->save();
            }
        }


        $this->resp->result = 1;
        if ($is_new) {
            $this->resp->redirect = APPURL."/accounts";
        } else {
            $this->resp->msg = __("Changes saved!");
        }
        $this->jsonecho();
    }
}