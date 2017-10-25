<?php
/**
 * Signup Controller
 */
class SignupController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");

        if ($AuthUser) {
            header("Location: ".APPURL."/post");
            exit;
        }

        $Package = Controller::model("Package", Input::get("package"));

        $this->setVariable("TimeZones", getTimezones())
             ->setVariable("Package", $Package);

        if (Input::post("action") == "signup") {
            $this->signup();
        }

        $this->view("signup", "site");
    }


    /**
     * Signup
     * @return void
     */
    private function signup()
    {
        $errors = [];

        $required_fields  = [
            "firstname", "lastname", "email", 
            "password", "password-confirm", "timezone"
        ];

        $required_ok = true;
        foreach ($required_fields as $field) {
            if (!Input::post($field)) {
                $required_ok = false;
            }
        }

        if (!$required_ok) {
            $errors[] = __("All fields are required");
        }


        if (empty($errors)) {
            if (!filter_var(Input::post("email"), FILTER_VALIDATE_EMAIL)) {
                $errors[] = __("Email is not valid!");
            } else {
                $User = Controller::model("User", Input::post("email"));
                if ($User->isAvailable()) {
                    $errors[] = __("Email is not available!");
                }
            }

            if (mb_strlen(Input::post("password")) < 6) {
                $errors[] = __("Password must be at least 6 character length!");
            } else if (Input::post("password-confirm") != Input::post("password")) {
                $errors[] = __("Password confirmation didn't match!");
            }


            if (empty($errors)) {
                $timezone = Input::post("timezone");
                if (!in_array(Input::post("timezone"), DateTimeZone::listIdentifiers())) {
                    $timezone = "UTC";
                }

                $trial = Controller::model("GeneralData", "free-trial");
                $trial_size = (int)$trial->get("data.size");
                if ($trial_size == "-1") {
                    $expire_date = "2050-12-12 23:59:59";
                } else if ($trial_size > 0) {
                    $expire_date = date("Y-m-d H:i:s", time() + $trial_size * 86400);
                } else {
                    $expire_date = date("Y-m-d H:i:s", time());
                }

                $settings = json_decode($trial->get("data"));
                unset($settings->size);

                $preferences = [
                    "timezone" => $timezone,
                    "dateformat" => "Y-m-d",
                    "timeformat" => "24"
                ];

                $User->set("email", strtolower(Input::post("email")))
                     ->set("password", 
                           password_hash(Input::post("password"), PASSWORD_DEFAULT))
                     ->set("firstname", Input::post("firstname"))
                     ->set("lastname", Input::post("lastname"))
                     ->set("settings", json_encode($settings))
                     ->set("preferences", json_encode($preferences))
                     ->set("is_active", 1)
                     ->set("expire_date", $expire_date)
                     ->save();

                try {
                    // Send notification emails to admins
                    \Email::sendNotification("new-user", ["user" => $User]);
                } catch (\Exception $e) {
                    // Failed to send notification email to admins
                    // Do nothing here, it's not critical error
                }


                // Fire user.signup event
                Event::trigger("user.signup", $User);


                $Package = Controller::model("Package", Input::post("package"));
                if ($Package->isAvailable()) {
                    $continue = APPURL . "/renew?package=" . $Package->get("id");
                } else {
                    $continue = APPURL . "/post";
                }

                // Logging in
                setcookie("nplh", $User->get("id").".".md5($User->get("password")), 0, "/");


                header("Location: ".$continue);
                exit;
            }
        }

        $this->setVariable("FormErrors", $errors);
        
        return $this;
    }
}