<?php 
namespace OneFileManager;


/**
 * Console to run commands
 */
class Console
{
    // OneFileManager\FileManager instance that this object belongs to
    public $manager;

    // OneFileManager\Connector instance
    private $connector;

    // Database connection
    private $connection;

    // Sql Clauses
    private $clauses;
    private $raw_clauses;

    // Input data
    private $input_data;


    /**
     * summary
     */
    public function __construct(FileManager $manager)
    {
        $this->manager = $manager;

        $this->connector = $this->manager->getConnector();
        $this->connection = $this->connector->getConnection();

        $this->clauses = [];
        $this->raw_clauses = [];
        if ($this->connector->getOption("user_id")) {
            $this->clauses["user_id"] = $this->connector->getOption("user_id");
        }

        $this->outdata = new \stdClass;
    }

    /**
     * Set input data
     * @param mixed $data Input data, generally stdClass instance
     */
    public function setInputData($data)
    {
        $this->input_data = $data;
        return $this;
    }


    /**
     * Get input data
     * @return mixed Input data
     */
    public function getInputData()
    {
        return $this->input_data;
    }


    /**
     * Run SQL query
     * @param  [type] $clauses [description]
     * @return [type]          [description]
     */
    public function runsql($sql, $clauses = null, $raw_clauses = null)
    {
        $params = [];

        $clausess_sql = "";

        if ($clauses || $raw_clauses) {
            $sql_arr = [];

            if ($clauses) {
                foreach ($clauses as $key => $value) {
                    $sql_arr[] = $key . " = ? ";
                    $params[] = $value;
                }
            }

            if ($raw_clauses) {
                foreach ($raw_clauses as $rc) {
                    $sql_arr[] = $rc;
                }
            }

            $clauses_sql = " WHERE " . implode(" AND ", $sql_arr);
        }

        $sql = str_replace("{clauses_sql}", $clauses_sql, $sql);

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }


    /**
     * Add a new record to file data table
     * @param array $data 
     */
    public function addDBRecord($data = array()) 
    {
        $data = array_merge([
            "id" => null,
            "user_id" => $this->connector->getOption("user_id"),
            "title" => "",
            "info" => "",
            "filename" => uniqid(),
            "filesize" => 0,
            "date" => date("Y-m-d H:i:s")
        ], $data);

        $sql = "INSERT INTO ".$this->connector->getOption("table_name")." (id, user_id, title, info, filename, filesize, date) VALUES "
             . "(?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->getDBRecord($this->connection->lastInsertId());
    }


    /**
     * Get file data from files data table
     * @param  integer $fileid ID of the file
     * @return \PDO         $stmt
     */
    public function getDBRecord($fileid)
    {
        $stmt = $this->connection->prepare("SELECT * FROM ".$this->connector->getOption("table_name")." WHERE id = ?");
        $stmt->execute(array($fileid));

        return $stmt->rowCount() == 1 ? $stmt->fetch(\PDO::FETCH_OBJ) : false;
    }


    /**
     * Get total used storage size
     * @return void 
     */
    public function getUsedStorageSize()
    {
        $sql = "SELECT SUM(filesize) AS total FROM ".$this->connector->getOption("table_name")." {clauses_sql};";
        $clauses = $this->clauses;
        $stmt = $this->runsql($sql, $clauses);

        return $stmt->fetchColumn();
    }





    /**
     * Command: init
     * @return void        
     */
    public function init()
    {
        // Get total amount of files
        $sql = "SELECT COUNT(id) AS total FROM ".$this->connector->getOption("table_name")." {clauses_sql};";
        $clauses = $this->clauses;
        $stmt = $this->runsql($sql, $clauses);

        $this->outdata->total = $stmt->fetchColumn();
        $this->outdata->allow = $this->manager->getOption("allow");
        $this->outdata->deny = $this->manager->getOption("deny");
        $this->outdata->queue_size = $this->manager->getOption("queue_size");
        $this->outdata->max_file_size = $this->manager->getOption("max_file_size");
        $this->outdata->max_storage_size = $this->manager->getOption("max_storage_size");
        $this->outdata->used_storage_size = $this->getUsedStorageSize();

        return Common::success($this->outdata);
    }

    /**
     * Retrieve files
     * @return void
     */
    public function retrieve() 
    {
        $input = $this->getInputData();

        // Get retrieve files
        $clauses = $this->clauses;
        $raw_clauses = $this->raw_clauses;
        $sql = "SELECT * FROM ".$this->connector->getOption("table_name")." {clauses_sql} ORDER BY date DESC";
        
        if (isset($input->start, $input->limit)) {
            $start = (int)$input->start;
            $limit = (int)$input->limit;

            $sql .= " LIMIT ".$start.", ".$limit.";";
        } else if (isset($input->ids)) {
            $ids = explode(",", $input->ids);
            $valid_ids = [];

            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id > 0 && !in_array($id, $valid_ids)) {
                    $valid_ids[] = $id;
                }
            }

            if (!$valid_ids) {
                $valid_ids[] = 0;
            }
            $raw_clauses[] = "id IN (".implode(",", $valid_ids).")";
        }
        $stmt = $this->runsql($sql, $clauses, $raw_clauses);

        $this->outdata->files = [];
        while ($r = $stmt->fetch(\PDO::FETCH_OBJ)) {
            if ($r->filename) {
                $filepath = $this->manager->getOption("path") . $r->filename;

                if (file_exists($filepath)) {
                    $ext = strtolower(pathinfo($r->filename, PATHINFO_EXTENSION));

                    $denied_exts = $this->manager->getOption("deny");
                    $allowed_exts = $this->manager->getOption("allow");

                    $allowed = true;
                    if (is_array($denied_exts) && in_array($ext, $denied_exts)) {
                        $allowed = false;
                    } else if ($allowed_exts && !in_array($ext, $allowed_exts)) {
                        $allowed = false;
                    }

                    if ($allowed) {
                        $this->outdata->files[] = [
                            "id" => $r->id,
                            "title" => $r->title,
                            "info" => $r->info,
                            "filename" => $r->filename,
                            "filesize" => $r->filesize,
                            "ext" => $ext,
                            "url" => $this->manager->getOption("url") . $r->filename,
                            "date" => $r->date,
                            "icon" => $ext == "mp4" ? "mdi mdi-play" : false
                        ];
                    }
                }
            }
        }
    
        return Common::success($this->outdata);
    }


    /**
     * Remove file
     * @return void
     */
    public function remove() 
    {
        $input = $this->getInputData();

        if (!isset($input->id)) {
            return Common::error("File ID is required");
        }

        
        // Get file data
        $sql = "SELECT * FROM ".$this->connector->getOption("table_name")." {clauses_sql} LIMIT 1";
        $clauses = $this->clauses;
        $clauses["id"] = $input->id;
        $stmt = $this->runsql($sql, $clauses);

        if ($stmt->rowCount() == 1) {
            $file = $stmt->fetch(\PDO::FETCH_OBJ);

            // Get remove file
            // Author has been set during the configuration,
            // so this is secure.
            $sql = "DELETE FROM ".$this->connector->getOption("table_name")." {clauses_sql}";
            $clauses = $this->clauses;
            $clauses["id"] = $file->id;
            $stmt = $this->runsql($sql, $clauses);

            // Remove actual file
            @unlink($this->manager->getOption("path") . $file->filename);
        }

        $this->outdata->max_storage_size = $this->manager->getOption("max_storage_size");
        $this->outdata->used_storage_size = $this->getUsedStorageSize();

        return Common::success($this->outdata);
    }


    /**
     * Upload file
     * @return void
     */
    public function upload() 
    {
        $input = $this->getInputData();

        if (!isset($input->type) || !in_array($input->type, array("url", "file"))) {
            return Common::error("Missing/Invalid type");
        }

        if ($input->type == "url") {
            $res = $this->grabFromURL($input->file);
        } else if ($input->type == "file") {
            $this->uploadFile();
        }
    }


    /**
     * Upload file from $_FILE
     * @return \stdClass Result data
     */
    private function uploadFile()
    {
        if (empty($_FILES["file"])) {
            return Common::error("Missing/Empty file");
        }


        // Check file size
        if ($this->manager->getOption("max_file_size") && 
            $this->manager->getOption("max_file_size") > 0 &&
            $this->manager->getOption("max_file_size") < $_FILES["file"]["size"]) 
        {
            return Common::error(__("File size exceeds max allowed file size."));
        } 

        if ($this->manager->getOption("max_file_size") <= 0) {
            return Common::error(__("File size exceeds max allowed file size."));  
        }

        if ($_FILES["file"]["size"] < 1) {
            return Common::error("Empty file");
        } 

        if ($this->manager->getOption("max_storage_size") && 
            $this->manager->getOption("max_storage_size") > 0 &&
            $this->manager->getOption("max_storage_size") < $this->getUsedStorageSize() + $_FILES["file"]["size"])
        {
            return Common::error(__("There is not enough storage to upload this file"));
        }

        // Check file extension
        $ext = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
        $denied_exts = $this->manager->getOption("deny");
        $allowed_exts = $this->manager->getOption("allow");

        $allowed = true;
        if (is_array($denied_exts) && in_array($ext, $denied_exts)) {
            $allowed = false;
        } else if ($allowed_exts && !in_array($ext, $allowed_exts)) {
            $allowed = false;
        }

        if (!$allowed) { 
            return Common::error(__("File type is not allowed."));
        }

        // Move uploaded file
        $filename = uniqid(readableRandomString(8)."-") . "." .$ext;
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], 
                                $this->manager->getOption("path") . $filename)) 
        {
            return Common::error(__("Couldn't save uploaded file."));
        }

        // Process the media
        $filename = $this->processMedia($filename);
        // File type might be changed, 
        // Get file extension again
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));


        // Add file data to filed data table
        $file = $this->addDBRecord([
            "title" => $_FILES["file"]["name"],
            "filename" => $filename,
            "filesize" => $_FILES["file"]["size"]
        ]);

        if (!$file) {
            unlink($this->manager->getOption("path") . $filename);
            return Common::error(__("Couldn't save uploaded file data."));
        }

        $this->outdata->file = [
            "id" => $file->id,
            "title" => $file->title,
            "info" => $file->info,
            "filename" => $file->filename,
            "filesize" => $file->filesize,
            "ext" => $ext,
            "url" => $this->manager->getOption("url") . $file->filename,
            "date" => $file->date,
            "icon" => $ext == "mp4" ? "mdi mdi-play" : false
        ];
        $this->outdata->max_storage_size = $this->manager->getOption("max_storage_size");
        $this->outdata->used_storage_size = $this->getUsedStorageSize();
      
        return Common::success($this->outdata);
    }


    /**
     * Grab file from $url
     * @param  [string] $url File URL
     * @return \stdClass Result data
     */
    private function grabFromURL($url)
    {
        $grabber = new FileGrabber($this);
        $grabber->setUrl($url);
        return $grabber->grab();
    }

    /**
     * Process the media file
     * Resize, convert, crop, watermark etc..
     * @param  string $filename Basename of the file
     * @return string           Processed media filename (not full path)
     */
    public function processMedia($filename)
    {
        $input = $this->getInputData();

        if (isset($input->keep_original_file) && $input->keep_original_file) {
            // Keep this file as original
            // There is no need to process this file
            return $filename;
        }


        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, ["jpeg", "jpg", "png"])) {
            $filename = $this->processImage($filename);
        } else if (in_array($ext, ["mp4"])) {
            $filename = $this->processVideo($filename);
        }

        return $filename;
    }

    /**
     * Process the images
     * @param  string $filename Base name of the file 
     * @return string           Processed media filename (not full path)
     */
    private function processImage($filename) 
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext == "png") {
             $image = new \claviska\SimpleImage;
             try {
                $new_filename = uniqid(readableRandomString(8)."-") . ".jpg";
                $image->fromFile($this->manager->getOption("path") . $filename)
                       ->toFile($this->manager->getOption("path") . $new_filename, "image/jpeg");
                @unlink($this->manager->getOption("path") . $filename);
                $ext = "jpg";
                $filename = $new_filename;
             } catch (\Exception $e) {
                 return Common::error($e->getMessage());
             }
        }

        if ($ext == "jpeg" || $ext == "jpg") {
            $image = new \claviska\SimpleImage;
            $image->fromFile($this->manager->getOption("path") . $filename)
                  ->autoOrient();
            $width = $image->getWidth();
            if ($width < 320) {
                unlink($this->manager->getOption("path") . $filename);
                return Common::error(__("Image is to small!"));
            } else if ($width > \InstagramAPI\MediaAutoResizer::MAX_WIDTH) {
                try {
                    $image->resize(\InstagramAPI\MediaAutoResizer::MAX_WIDTH)
                          ->toFile($this->manager->getOption("path") . $filename);
                } catch (Exception $e) {
                    return Common::error($e->getMessage());
                }
            }
        }

        return $filename;
    }


    /**
     * Process the videos
     * @param  string $filename Basename of the file
     * @return string           Processed media filename (not full path)
     */
    private function processVideo($filename)
    {
        // There is nothing to do here, for now
        // Return same $filename
        return $filename;
    }
}
