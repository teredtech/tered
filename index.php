<?php
// Start session
session_start();

// Define basic constants
define("VERSION", "030003"); // Used for cache control
define("APP_VERSION", "3.0.3");
define("ENVIRONMENT", "installation"); // [development|production|installation]

// Path to root directory of app.
define("ROOTPATH", dirname(__FILE__));

// Path to app folder.
define("APPPATH", ROOTPATH."/app");


// Check if SSL enabled
$ssl = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off"
     ? true
     : false;
define("SSL_ENABLED", $ssl);

// Define APPURL
$app_url = (SSL_ENABLED ? "https" : "http")
         . "://"
         . $_SERVER["SERVER_NAME"]
         . (dirname($_SERVER["SCRIPT_NAME"]) == DIRECTORY_SEPARATOR ? "" : "/")
         . trim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");
define("APPURL", $app_url);


/**
 * Check ENVIRONMENT
 */
error_reporting(E_ALL);
if (ENVIRONMENT == "installation") {
    header("Location: ".APPURL."/install");
    exit;
} else if (ENVIRONMENT == "development") {
    ini_set('display_errors', 1);
} else if (ENVIRONMENT == "production") {
    ini_set('display_errors', 0);
} else {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Environment is invalid. Please contact developer for more information.';
    exit;
}


require_once APPPATH.'/autoload.php';
require_once APPPATH.'/config/config.php';
require_once APPPATH."/helpers/helpers.php";


// Start routing...
$Router = new AltoRouter();
    $base_path = trim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");
    $Router->setBasePath($base_path ? "/".$base_path : "");

    // Language slug
    $langs = [];
    foreach (Config::get("applangs") as $al) {
        if (!in_array($al["code"], $langs)) {
            $langs[] = $al["code"];
        }

        if (!in_array($al["shortcode"], $langs)) {
            $langs[] = $al["shortcode"];
        }
    }
    $langslug = $langs ? "[".implode("|", $langs).":lang]" : "";


    // Index (Landing Page)
    //
    // Replace "Index" with "Login" to completely disable Landing page
    // After this change, Login page will be your default landing page
    //
    // This is usefull in case of self use, or having different
    // landing page in different address. For ex: you can install the script
    // to subdirectory or subdomain of your wordpress website.
    $Router->map("GET|POST", "/", "Index");
    $Router->map("GET|POST", "/".$langslug."?/", "Index");

    // Login
    $Router->map("GET|POST", "/".$langslug."?/login/?", "Login");

    // Signup
    //
    //  Remove or comment following 3 lines to completely
    //  disable signup page. This might be usefull in case
    //  of self use of the script
    $Router->map("GET|POST", "/".$langslug."?/signup/?", "Signup");

    // Logout
    $Router->map("GET", "/".$langslug."?/logout/?", "Logout");

    // Recovery
    $Router->map("GET|POST", "/".$langslug."?/recovery/?", "Recovery");
    $Router->map("GET|POST", "/".$langslug."?/recovery/[i:id].[a:hash]/?", "PasswordReset");



    // New|Edit Post
    $Router->map("GET|POST", "/post/[i:id]?/?", "Post");


    // Instagram Accounts
    $Router->map("GET|POST", "/accounts/?", "Accounts");
    // New Instagram Account
    $Router->map("GET|POST", "/accounts/new/?", "Account");
    // Edit Instagram Account
    $Router->map("GET|POST", "/accounts/[i:id]/?", "Account");


    // Caption Templates
    $Router->map("GET|POST", "/captions/?", "Captions");
    // New Caption Template
    $Router->map("GET|POST", "/captions/new/?", "Caption");
    // Edit Caption Template
    $Router->map("GET|POST", "/captions/[i:id]/?", "Caption");


    // Settings
    $settings_pages = [
      "site", "logotype", "other",
      "google-analytics", "google-drive", "dropbox", "onedrive", "paypal", "stripe", "facebook",
      "proxy",

      "notifications", "smtp"
    ];
    $Router->map("GET|POST", "/settings/[".implode("|", $settings_pages).":page]?/?", "Settings");


    // Packages
    $Router->map("GET|POST", "/packages/?", "Packages");
    // New Package
    $Router->map("GET|POST", "/packages/new/?", "Package");
    // Edit Package
    $Router->map("GET|POST", "/packages/[i:id]/?", "Package");
    // Free Trial Package
    $Router->map("GET|POST", "/packages/trial/?", "TrialPackage");


    // Users
    $Router->map("GET|POST", "/users/?", "Users");
    // New User
    $Router->map("GET|POST", "/users/new/?", "User");
    // Edit User
    $Router->map("GET|POST", "/users/[i:id]/?", "User");
    $Router->map("GET|POST", "/profile/?", "Profile");


    // File Manager (Connector for inline)
    $Router->map("GET|POST", "/file-manager/connector/?", "FileManager");


    // Schedule Calendar
    $Router->map("GET|POST", "/schedule-calendar/?", "ScheduleCalendar");
    $Router->map("GET|POST", "/schedule-calendar/[i:year]/[i:month]/?", "ScheduleCalendar");
    // Scheduled Posts
    $Router->map("GET|POST", "/schedule-calendar/[i:year]/[i:month]/[i:day]?", "ScheduleCalendar");


    // Proxies
    $Router->map("GET|POST", "/proxies/?", "Proxies");
    // New Proxy
    $Router->map("GET|POST", "/proxies/new/?", "Proxy");
    // Edit Proxy
    $Router->map("GET|POST", "/proxies/[i:id]/?", "Proxy");


    // Statistics
    $Router->map("GET|POST", "/statistics/?", "Statistics");

    // Expired
    $Router->map("GET", "/expired/?", "Expired");
    // Renew
    $Router->map("GET|POST", "/renew/?", "Renew");


    // Checkout Results
    $Router->map("GET|POST", "/checkout/[i:id].[a:hash]/?", "CheckoutResult");
    $Router->map("GET|POST", "/checkout/error/?", "CheckoutResult");


    // Cron
    $Router->map("GET", "/cron/?", "Cron");


    // Plugins (Modules)
    $Router->map("GET|POST", "/plugins/?", "Plugins");
    // Upload plugin
    $Router->map("GET|POST", "/plugins/install/?", "Plugin");
    // Install plugin
    $Router->map("GET|POST", "/plugins/install/[a:hash]/?", "Plugin");


$App = new App;
$App->setRouter($Router)->process();
