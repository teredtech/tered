<?php 
/**
 * Users model
 *
 * @version 1.0
 * @author Onelab <hello@onelab.co> 
 * 
 */
class UsersModel extends DataList
{	
	/**
	 * Initialize
	 */
	public function __construct()
	{
		$this->setQuery(DB::table(TABLE_PREFIX.TABLE_USERS));
	}

    public function fetchData()
    {
        $this->getQuery()
             ->leftJoin(
                    TABLE_PREFIX.TABLE_PACKAGES,
                    TABLE_PREFIX.TABLE_USERS.".package_id",
                    "=",
                    TABLE_PREFIX.TABLE_PACKAGES.".id"
                );
        $this->paginate();

        $this->getQuery()
             ->select(TABLE_PREFIX.TABLE_USERS.".*")
             ->select(TABLE_PREFIX.TABLE_PACKAGES.".title");
        $this->data = $this->getQuery()->get();
        return $this;
    }
}
