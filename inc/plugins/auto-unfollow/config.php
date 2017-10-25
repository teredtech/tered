<?php if (!defined('APP_VERSION')) die("Yo, what's up?"); ?>
<?php 
    return [
        "idname" => "auto-unfollow",
        "plugin_name" => "Auto Unfollow",
        "plugin_uri" => "http://getnextpost.io",
        "author" => "Nextpost",
        "author_uri" => "http://getnextpost.io",
        "version" => "2.0",
        "desc" => "Save time and let the system unfollow your followers regularly just one click.",
        "icon_style" => "background-color: #00DBDE; background: linear-gradient(136.03deg, #00DBDE 0%, #FC00FF 100%); color: #fff;",
        "settings_page_uri" => APPURL."/e/auto-unfollow/settings"
    ];
