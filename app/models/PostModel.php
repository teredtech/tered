<?php 
	/**
	 * Post Model
	 *
	 * @version 1.0
	 * @author Onelab <hello@onelab.co> 
	 * 
	 */
	
	class PostModel extends DataEntry
	{	
		/**
		 * Extend parents constructor and select entry
		 * @param mixed $uniqid Value of the unique identifier
		 */
	    public function __construct($uniqid=0)
	    {
	        parent::__construct();
	        $this->select($uniqid);
	    }



	    /**
	     * Select entry with uniqid
	     * @param  int|string $uniqid Value of the any unique field
	     * @return self       
	     */
	    public function select($uniqid)
	    {
	    	if (is_int($uniqid) || ctype_digit($uniqid)) {
	    		$col = $uniqid > 0 ? "id" : null;
	    	} else {
	    		$col = null;
	    	}

	    	if ($col) {
		    	$query = DB::table(TABLE_PREFIX.TABLE_POSTS)
			    	      ->where($col, "=", $uniqid)
			    	      ->limit(1)
			    	      ->select("*");
		    	if ($query->count() == 1) {
		    		$resp = $query->get();
		    		$r = $resp[0];

		    		foreach ($r as $field => $value)
		    			$this->set($field, $value);

		    		$this->is_available = true;
		    	} else {
		    		$this->data = array();
		    		$this->is_available = false;
		    	}
	    	}

	    	return $this;
	    }


	    /**
	     * Extend default values
	     * @return self
	     */
	    public function extendDefaults()
	    {
	    	$defaults = array(
	    		"status" => "saved",
	    		"user_id" => 0,
	    		"type" => "",
	    		"caption" => "",
	    		"media_ids" => "",
	    		"account_id" => 0,
	    		"is_scheduled" => 0,
	    		"create_date" => date("Y-m-d H:i:s"),
	    		"schedule_date" => date("Y-m-d H:i:s"),
	    		"publish_date" => date("Y-m-d H:i:s"),
	    		"is_hidden" => 0,
	    		"data" => "{}",
	    	);


	    	foreach ($defaults as $field => $value) {
	    		if (is_null($this->get($field)))
	    			$this->set($field, $value);
	    	}
	    }


	    /**
	     * Insert Data as new entry
	     */
	    public function insert()
	    {
	    	if ($this->isAvailable())
	    		return false;

	    	$this->extendDefaults();

	    	$id = DB::table(TABLE_PREFIX.TABLE_POSTS)
		    	->insert(array(
		    		"id" => null,
		    		"status" => $this->get("status"),
		    		"user_id" => $this->get("user_id"),
		    		"type" => $this->get("type"),
		    		"caption" => $this->get("caption"),
		    		"media_ids" => $this->get("media_ids"),
		    		"account_id" => $this->get("account_id"),
		    		"is_scheduled" => $this->get("is_scheduled"),
		    		"create_date" => $this->get("create_date"),
		    		"schedule_date" => $this->get("schedule_date"),
		    		"publish_date" => $this->get("publish_date"),
		    		"is_hidden" => $this->get("is_hidden"),
		    		"data" => $this->get("data")
		    	));

	    	$this->set("id", $id);
	    	$this->markAsAvailable();
	    	return $this->get("id");
	    }


	    /**
	     * Update selected entry with Data
	     */
	    public function update()
	    {
	    	if (!$this->isAvailable())
	    		return false;

	    	$this->extendDefaults();

	    	$id = DB::table(TABLE_PREFIX.TABLE_POSTS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
		    		"status" => $this->get("status"),
		    		"user_id" => $this->get("user_id"),
		    		"type" => $this->get("type"),
		    		"caption" => $this->get("caption"),
		    		"media_ids" => $this->get("media_ids"),
		    		"account_id" => $this->get("account_id"),
		    		"is_scheduled" => $this->get("is_scheduled"),
		    		"create_date" => $this->get("create_date"),
		    		"schedule_date" => $this->get("schedule_date"),
		    		"publish_date" => $this->get("publish_date"),
		    		"is_hidden" => $this->get("is_hidden"),
		    		"data" => $this->get("data"),
		    	));

	    	return $this;
	    }


	    /**
		 * Remove selected entry from database
		 */
	    public function delete()
	    {
	    	if(!$this->isAvailable())
	    		return false;

	    	DB::table(TABLE_PREFIX.TABLE_POSTS)->where("id", "=", $this->get("id"))->delete();
	    	$this->is_available = false;
	    	return true;
	    }


	    /**
	     * Update current id of the post
	     * @param  integer $new_id 
	     * @param  string $idcol  
	     * @return boolean         
	     */
	    public function updateId($new_id, $idcol = "id")
	    {
	    	if (!$this->isAvailable()) {
	    		return false;
	    	}

	    	$current_id = $this->get($idcol);
	    	
	    	DB::table(TABLE_PREFIX.TABLE_POSTS)
	    		->where("id", "=", $this->get("id"))
		    	->update(array(
		    		"id" => $new_id
		    	));

		    $this->set("id", $new_id);
		    return true;
	    }
	}
	