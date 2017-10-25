<?php 
namespace OneFileManager;

/**
 * Common static methods
 */
class Common
{
    public static $print_output = true;

    /**
     * Contructor
     */
    public function __construct()
    {
    }

    /**
     * Set output mode
     * @param  bool $bool 
     * @return void       
     */
    public static function outputMode($bool)
    {
        self::$print_output = (bool)$bool;
    }

    /**
     * Output result and exit
     * @param  Mixed $code   
     * @param  Mixed $message 
     * @param  Mixed  $errors  [description]
     * @return void
     */
    public static function output($success=false, $message=null, $data = null)
    {
        $output = array();

        if ($success) {
            $output["success"] = (bool)$success;
        }

        if ($message) {
            $output["message"] = $message;
        }

        if ($data) {
            $output["data"] = $data;
        }

        if (self::$print_output) {
            echo empty($_GET["callback"]) 
                    ? json_encode($output) 
                    : $_GET["callback"]. "(" . json_encode($output) . ")";
            exit;
        } else {
            return $output;
        }
    }


    /**
     * Output error message
     * @param   $message 
     * @return [type]          [description]
     */
    public static function error($message, $data=null)
    {
        return self::output(false, $message, $data);
    }


    /**
     * Output success message
     * @param   $message 
     * @return [type]          [description]
     */
    public static function success($data=null)
    {
        return self::output(true, null, $data);
    }


    /**
     * Get MIME type from extension
     * @param  string $ext Extension
     * @return string|null      
     */
    public static function extToMime($ext)
    {
        $mime_types = require __DIR__."/ext2mime.php";

        if (substr($ext, 0, 1) != ".") {
            $ext = ".".$ext;
        }

        return isset($mime_types[$ext]) ? $mime_types[$ext] : null;
    }

    /**
     * Get Extension from mime-type
     * @param  string $mime Extension
     * @return string|null      
     */
    public static function mimeToExt($mime)
    {
        $extensions = require __DIR__."/mime2ext.php";
        return isset($extensions[$mime]) ? $extensions[$mime] : null;
    }
}
