<?php 
/**
 * Get active theme path
 * @return string 
 */
function active_theme_path()
{
    return THEMES_PATH . "/"
                       . (site_settings("theme") ? site_settings("theme") 
                                                 : "default");
}


/**
 * Get active theme url
 * @return string 
 */
function active_theme_url()
{
    return THEMES_URL . "/"
                      . (site_settings("theme") ? site_settings("theme") 
                                                : "default");
}