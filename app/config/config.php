<?php 
require_once APPPATH.'/config/db.config.php';
require_once APPPATH.'/config/i18n.config.php';
require_once APPPATH.'/config/common.config.php';

// ASCII Secure random crypto key
define("CRYPTO_KEY", "def00000d30018427fe8e8d01b11135ccaafa3dbb494053c7e1b3fd158e07b42cb6e176becb7ccde02a85d006a4c9471f475970b4799568c89dccd5d50fcf9c7ea21c89f");

// General purpose salt
define("NP_SALT", "JT7RWOwsgWZFRCGm");


// Path to instagram sessions directory
define("SESSIONS_PATH", APPPATH . "/sessions");
// Path to temporary files directory
define("TEMP_PATH", ROOTPATH . "/assets/uploads/temp");


// Path to themes directory
define("THEMES_PATH", ROOTPATH . "/inc/themes");
// URI of themes directory
define("THEMES_URL", APPURL . "/inc/themes");


// Path to plugins directory
define("PLUGINS_PATH", ROOTPATH . "/inc/plugins");
// URI of plugins directory
define("PLUGINS_URL", APPURL . "/inc/plugins");

// Path to ffmpeg binary executable
// NULL means it's been installed on global path
// If you set the value other than null, then it will only be 
// validated during posting the videos
define("FFMPEGBIN", NULL);

// Path to ffprobe binary executable
// NULL means it's been installed on global path
// If you set the value other than null, then it will only be 
// validated during posting the videos
define("FFPROBEBIN", NULL);
