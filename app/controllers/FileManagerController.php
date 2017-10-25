<?php
/**
 * FileManager Controller
 */
class FileManagerController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");

        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }

        $this->connect();
    }



    /**
     * Connect to file manager
     * @return void
     */
    private function connect()
    {   
        $AuthUser = $this->getVariable("AuthUser");

        $connector_options = [
            "host" => DB_HOST,
            "database" => DB_NAME,
            "username" => DB_USER,
            "password" => DB_PASS,
            "charset" => DB_ENCODING,
            "table_name" => TABLE_PREFIX.TABLE_FILES,
            "opions" => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ),

            "user_id" => $AuthUser->get("id")
        ];
        $Connector = new OneFileManager\Connector;
        $Connector->setOptions($connector_options)->init();



        /**
         * File manager configurations
         */
        $path_to_users_directory = ROOTPATH."/assets/uploads/"
                                 . $AuthUser->get("id")
                                 . "/";

        if (!file_exists($path_to_users_directory)) {
            mkdir($path_to_users_directory);
        } 

        $user_dir_url = APPURL."/assets/uploads/"
                      . $AuthUser->get("id")
                      . "/";

        $options = [
            "path" => $path_to_users_directory,
            "url" => $user_dir_url,

            "allow" => array("jpeg", "jpg", "png", "mp4"),
            "queue_size" => 10
        ];

        if ($AuthUser->get("settings.storage.file") >= 0) {
            $options["max_file_size"] = (double)$AuthUser->get("settings.storage.file") * 1024*1024;
        }

        if ($AuthUser->get("settings.storage.total") >= 0) {
            $options["max_storage_size"] = (double)$AuthUser->get("settings.storage.total") * 1024*1024;
        }


        $FileManager = new OneFileManager\FileManager;
        $FileManager->setOptions($options)
                    ->setConnector($Connector)
                    ->run();
    }
}
