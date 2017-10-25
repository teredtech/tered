<?php
/**
 * Email class to send advanced HTML emails
 *
 * @author Onelab <hello@onelab.co>
 */

class Email {
    /**
     * Email template html
     * @var string
     */
    public static $template;


    /**
     * Email and notification settings from database
     * @var DataEntry
     */
    public static $emailSettings;


    /**
     * Site settings
     * @var DataEntry
     */
    public static $siteSettings;


    public function __construct(){
        parent::__construct();

        // Get settings
        $emailSettings = self::getEmailSettings();

        // Get site name
        $siteSettings = self::getSiteSettings();

        //$this->CharSet = "UTF-8";
        //$this->isHTML();

        /*if ($emailSettings->get("data.smtp.host")) {
            $this->isSMTP();

            if ($emailSettings->get("data.smtp.from")) {
                $this->From = $emailSettings->get("data.smtp.from");
                $this->FromName = htmlchars($siteSettings->get("data.site_name"));
            }

            $this->Host = $emailSettings->get("data.smtp.host");
            $this->Port = $emailSettings->get("data.smtp.port");
            $this->SMTPSecure = $emailSettings->get("data.smtp.encryption");

            if ($emailSettings->get("data.smtp.auth")) {
                $this->SMTPAuth = true;
                $this->Username = $emailSettings->get("data.smtp.username");
                $this->Password = $emailSettings->get("data.smtp.password");
            }
        }*/
    }


    /**
     * Send email with $content
     * @param  string $content Email content
     * @return boolen          Sending result
     */
    public function sendmail($content){
        $html = self::getTemplate();
        $html = str_replace("{email_content}", $content, $html);

        //$this->Body = $html;

        //return $this->send();
    }


    /**
     * Get email settings
     * @return string|null
     */
    private static function getEmailSettings()
    {
        if (is_null(self::$emailSettings)) {
            self::$emailSettings = \Controller::model("GeneralData", "email-settings");
        }

        return self::$emailSettings;
    }

    /**
     * Get site settings
     * @return string|null
     */
    private static function getSiteSettings()
    {
        if (is_null(self::$siteSettings)) {
            self::$siteSettings = \Controller::model("GeneralData", "settings");
        }

        return self::$siteSettings;
    }


    /**
     * Get template HTML
     * @return string
     */
    private static function getTemplate()
    {
        if (!self::$template) {
            ob_start();
            $Settings = self::getSiteSettings();
            require_once APPPATH."/inc/email-template.inc.php";
            $html = ob_get_contents();
            ob_clean();

            self::$template = $html;
        }

        return self::$template;
    }




    /**
     * Send notifications
     * @param  string $type notification type
     * @return [type]
     */
    public static function sendNotification($type = "new-user", $data = [], $option=null)
    {
        switch ($type) {
            case "new-user":
                return self::sendNewUserNotification($data);
                break;

            case "new-payment":
                return self::sendNewPaymentNotification($data);
                break;

            case "password-recovery":
                return self::sendPasswordRecoveryEmail($data);
                break;
            case "welcome":
            	return self::sendWelcomeEmail($data);
            	break;

            case "require-login":
            	return self::sendRequireLoginEmail($data, $option);
            	break;

            default:
                break;
        }
    }


    /**
     * Send notification email to admins about new users
     * @return bool
     */
    private static function sendNewUserNotification($data = [])
    {
        $emailSettings = self::getEmailSettings();
        $siteSettings = self::getSiteSettings();
        $user = $data["user"];

        if (!$emailSettings->get("data.notifications.emails") ||
            !$emailSettings->get("data.notifications.new_user"))
        {
            return false;
        }


        $emailbody = "<p>Hello, </p>"
                   . "<p>Someone signed up at <a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a> with following data:</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<div><strong>Firstname:</strong> ".htmlchars($user->get("firstname"))."</div>"
                   . "<div><strong>Lastname:</strong> ".htmlchars($user->get("lastname"))."</div>"
                   . "<div><strong>Email:</strong> ".htmlchars($user->get("email"))."</div>"
                   . "<div><strong>Timezone:</strong> ".htmlchars($user->get("preferences.timezone"))."</div>"
                   . "</div>";
        $apiKey = "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";

	$from = new SendGrid\Email("New Registration", "noreply@tered.tech");
	$subject = __("New Registration");
	$tos = explode(",", $emailSettings->get("data.notifications.emails"));

	//$to = new SendGrid\Email($user->get("firstname"), $user->get("email"));

	$content = new SendGrid\Content("text/html", $emailbody);

	$sg = new \SendGrid($apiKey);
	foreach($tos as $to_user){
	    $to = new SendGrid\Email("test", $to_user);
	    $mail = new SendGrid\Mail($from, $subject, $to, $content);
	    $mail->setTemplateId("25a776ed-ee46-4b75-995f-ca28fe0fa4b2");
	    $response = $sg->client->mail()->send()->post($mail);
	}
	return $response->statusCode();

/*        $mail = new Email;
        $mail->Subject = "New Registration";

        $tos = explode(",", $emailSettings->get("data.notifications.emails"));
        foreach ($tos as $to) {
            $mail->addAddress(trim($to));
        }


        $emailbody = "<p>Hello, </p>"
                   . "<p>Someone signed up in <a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a> with following data:</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<div><strong>Firstname:</strong> ".htmlchars($user->get("firstname"))."</div>"
                   . "<div><strong>Lastname:</strong> ".htmlchars($user->get("lastname"))."</div>"
                   . "<div><strong>Email:</strong> ".htmlchars($user->get("email"))."</div>"
                   . "<div><strong>Timezone:</strong> ".htmlchars($user->get("preferences.timezone"))."</div>"
                   . "</div>";

        return $mail->sendmail($emailbody);*/
    }


    /**
     * Send notification email to admins about new payments
     * @return bool
     */
    private static function sendNewPaymentNotification($data = [])
    {
      $emailSettings = self::getEmailSettings();
      $siteSettings = self::getSiteSettings();
      $user = $data["user"];

      if (!$emailSettings->get("data.notifications.emails") ||
          !$emailSettings->get("data.notifications.new_user"))
      {
          return false;
      }
      $emailbody = "<p>Hello, </p>"
                   . "<p>New payment recevied in <a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a> with following data:</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<div><strong>Payment Reason:</strong> Package (account) renew</div>"
                   . "<div><strong>User:</strong> ".htmlchars($user->get("firstname")." ".$user->get("lastname"))."&lt;".htmlchars($user->get("email"))."&gt;</div>"
                   . "<div><strong>Order ID:</strong> ".$order->get("id")."</div>"
                   . "<div><strong>Package:</strong> ".htmlchars($order->get("data.package.title"))."</div>"
                   . "<div><strong>Plan:</strong> ".ucfirst($order->get("data.plan"))."</div>"
                   . "<div><strong>Payment Gateway:</strong> ".ucfirst($order->get("payment_gateway"))."</div>"
                   . "<div><strong>Payment ID:</strong> ".htmlchars($order->get("payment_id"))."</div>"
                   . "<div><strong>Amount:</strong> ".$order->get("paid")." ".$order->get("currency")."</div>"
                   . "</div>";

      $apiKey = "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";

      $from = new SendGrid\Email("New Payment", "noreply@tered.tech");
      $subject = __("New Payment");
      $tos = explode(",", $emailSettings->get("data.notifications.emails"));
      //$to = new SendGrid\Email($user->get("firstname"), $user->get("email"));
      $content = new SendGrid\Content("text/html", $emailbody);

      $sg = new \SendGrid($apiKey);
      foreach($tos as $to_user){
        $to = new SendGrid\Email("test", $to_user);
        $mail = new SendGrid\Mail($from, $subject, $to, $content);
        $mail->setTemplateId("23e56cd4-f188-498a-bc10-2b982e143413");
        $response = $sg->client->mail()->send()->post($mail);
      }

      $response = $sg->client->mail()->send()->post($mail);
      return $response->statusCode();

        /*$mail = new Email;
        $mail->Subject = "New Payment";

        $tos = explode(",", $emailSettings->get("data.notifications.emails"));
        foreach ($tos as $to) {
            $mail->addAddress(trim($to));
        }

        $order = $data["order"];
        $user = \Controller::model("User", $order->get("user_id"));

        $emailbody = "<p>Hello, </p>"
                   . "<p>New payment recevied in <a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a> with following data:</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<div><strong>Payment Reason:</strong> Package (account) renew</div>"
                   . "<div><strong>User:</strong> ".htmlchars($user->get("firstname")." ".$user->get("lastname"))."&lt;".htmlchars($user->get("email"))."&gt;</div>"
                   . "<div><strong>Order ID:</strong> ".$order->get("id")."</div>"
                   . "<div><strong>Package:</strong> ".htmlchars($order->get("data.package.title"))."</div>"
                   . "<div><strong>Plan:</strong> ".ucfirst($order->get("data.plan"))."</div>"
                   . "<div><strong>Payment Gateway:</strong> ".ucfirst($order->get("payment_gateway"))."</div>"
                   . "<div><strong>Payment ID:</strong> ".htmlchars($order->get("payment_id"))."</div>"
                   . "<div><strong>Amount:</strong> ".$order->get("paid")." ".$order->get("currency")."</div>"
                   . "</div>";

        return $mail->sendmail($emailbody);*/
    }


    /**
     * Send RequireLogin email to user
     * @return bool
     */
    private static function sendRequireLoginEmail($data, $option)
    {
      $siteSettings = self::getSiteSettings();
      $account = $option["account"];
      $user = $data["user"];
      $hash = sha1(uniqid(readableRandomString(10), true));

      $emailbody = "<p>".__("Hi %s", htmlchars($user->get("firstname"))).", </p>"
                   . "<p>".$account ->get("username")." account requires re-login</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . ""
                   . "</div>";
      $apiKey = "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";

      $from = new SendGrid\Email("The Tered Team", "noreply@tered.tech");
      $subject = __("Require Re-login");
      $to = new SendGrid\Email($user->get("firstname"), $user->get("email"));
      $content = new SendGrid\Content("text/html", $emailbody);
      $mail = new SendGrid\Mail($from, $subject, $to, $content);

      $mail->setTemplateId("72195941-58fa-4d6e-b918-1e9357e6be64");
      $sg = new \SendGrid($apiKey);

      $response = $sg->client->mail()->send()->post($mail);
      return $response->statusCode();

    }

    /**
     * Send welcome email to user
     * @return bool
     */
    private static function sendWelcomeEmail($data)
    {
      $siteSettings = self::getSiteSettings();
      $user = $data["user"];
      $hash = sha1(uniqid(readableRandomString(10), true));

      $emailbody = "<p>".__("Hi %s", htmlchars($user->get("firstname"))).", </p>"
                   . "<p>".__("Welcome to Tered")."</p>"
                   . "<p>".__("Account information:")."</p>"
                   . "<p><strong>Sign in to your account:</strong></p>"
                   . "<a href='http://app.tered.tech/login'>http://app.tered.tech/login</a>"
                   . "<br><br>"
                   . "<p><strong>Email:</strong></p>"
                   . htmlchars($user->get("email"))
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "Enjoy our benefits here!"
                   . "</div>";

      $apiKey = "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";

      $from = new SendGrid\Email("The Tered Team", "noreply@tered.tech");
      $subject = __("Welcome to Tered");
      $to = new SendGrid\Email($user->get("firstname"), $user->get("email"));
      $content = new SendGrid\Content("text/html", $emailbody);
      $mail = new SendGrid\Mail($from, $subject, $to, $content);

      $mail->setTemplateId("45b8b980-11a0-493a-a14d-b5fa0dbc5260");
      $sg = new \SendGrid($apiKey);

      $response = $sg->client->mail()->send()->post($mail);
      return $response->statusCode();
    }

    /**
     * Send recovery instructions to the user
     * @return bool
     */
    private static function sendPasswordRecoveryEmail($data = [])
    {
      $siteSettings = self::getSiteSettings();
      $user = $data["user"];
      $hash = sha1(uniqid(readableRandomString(10), true));
      $user->set("data.recoveryhash", $hash)->save();

      $emailbody = "<p>".__("Hi %s", htmlchars($user->get("firstname"))).", </p>"
                   . "<p>".__("Someone requested password reset instructions for your account on %s. If this was you, click the button below to set new password for your account. Otherwise you can forget about this email. Your account is still safe.", "<a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a>")."</p>"
                   . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                   . "<a style='display: inline-block; background-color: #3b7cff; color: #fff; font-size: 14px; line-height: 24px; text-decoration: none; padding: 6px 12px; border-radius: 4px;' href='".APPURL."/recovery/".$user->get("id").".".$hash."'>".__("Reset Password")."</a>"
                   . "</div>";

      $apiKey = "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";
      // $apiKey = "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"; // API Key for "Tered API 1"if you want to try a different one.

      $from = new SendGrid\SendGrid\Email("The Tered Team", "noreply@tered.tech");
      $subject = __("Reset Your Password");
      $to = new SendGrid\Email($user->get("firstname"), $user->get("email"));
      $content = new SendGrid\Content("text/html", $emailbody);
      $mail = new SendGrid\Mail($from, $subject, $to, $content);
      $mail->setTemplateId("40ad2628-237f-40d1-87b4-7fe616b4f751");
      $sg = new \SendGrid($apiKey);

      $response = $sg->client->mail()->send()->post($mail);
      return $response->statusCode();

      /*$siteSettings = self::getSiteSettings();

      $mail = new Email;
      $mail->Subject = __("Password Recovery");
      $user = $data["user"];

      $hash = sha1(uniqid(readableRandomString(10), true));
      $user->set("data.recoveryhash", $hash)->save();

      $mail->addAddress($user->get("email"));

      $emailbody = "<p>".__("Hi %s", htmlchars($user->get("firstname"))).", </p>"
                 . "<p>".__("Someone requested password reset instructions for your account on %s. If this was you, click the button below to set new password for your account. Otherwise you can forget about this email. Your account is still safe.", "<a href='".APPURL."'>".htmlchars($siteSettings->get("data.site_name"))."</a>")."</p>"
                 . "<div style='margin-top: 30px; font-size: 14px; color: #9b9b9b'>"
                 . "<a style='display: inline-block; background-color: #3b7cff; color: #fff; font-size: 14px; line-height: 24px; text-decoration: none; padding: 6px 12px; border-radius: 4px;' href='".APPURL."/recovery/".$user->get("id").".".$hash."'>".__("Reset Password")."</a>"
                 . "</div>";

      return $mail->sendmail($emailbody);*/
    }
}
