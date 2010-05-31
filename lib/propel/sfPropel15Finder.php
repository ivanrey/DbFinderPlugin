<?php
/*
 * This file is part of the sfPropelFinder package.
 * 
 * (c) 2007 François Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
class sfPropel15Finder extends sfBasePropelFinder
{
	
	protected
		$queryClass;
		
	/**
	 * @var Criteria;
	 */	
	protected $criteria;
	
	/**
	 * @return ModelCriteria
	 */
	public function getCriteria()
	{
		return $this->criteria;		
	}
	
	public function setCriteria($criteria)
  {
    $this->criteria = $criteria;
		$this->queryClass = get_class($criteria);
    return $this;
  }
  
  public function reinitCriteria()
  { 
    return $this->setCriteria(PropelQuery::from($this->class.' '.$this->alias));
  }
  
  public function select($columnArray, $keyType = self::ASSOCIATIVE)
  {
  	throw new Exception('Implement me! (Custom formatter maybe)');
  }
  
// Finder Executers
  
  /**
   * Returns the number of records matching the finder
   *
   * @param Boolean $distinct Whether the count query has to add a DISTINCT keyword
   *
   * @return integer Number of records matching the finder
   */
  public function count($distinct = false)
  {
    if($cache = $this->cache)
    {
      $key = $this->getUniqueIdentifier().'_count';
      $ret = $cache->getIfSet($key);
      if($ret !== false)
      {
        return $ret;
      }
    }
    
    if($distinct)
    {
    	$this->getCriteria()->setDistinct();
    }
    
    $ret = $this->criteria->count($this->connection);
    if($cache)
    {
      $cache->set($key, $ret);
    }
    $this->cleanup();
    
    return $ret;
  }
   
  
  /**
   * Executes the finder and returns the matching Propel objects
   *
   * @param integer $limit Optional maximum number of results to retrieve
   *
   * @return array A list of BaseObject Propel objects
   */
  public function find($limit = null)
  {
    if($limit)
    {
      $this->criteria->setLimit($limit);
    }
    $ret = $this->getCriteria()->find($this->connection);
    $this->cleanup();
    
    return $ret;
  }
  
/**
   * Limits the search to a single result, and executes the finder
   * Returns the first Propel object matching the finder
   *
   * @return mixed a BaseObject object or null
   */
  public function findOne()
  {
    $this->criteria->setLimit(1);
    $ret = $this->getCriteria()->findOne($this->getConnection());
    $this->cleanup();
    
    return $ret;
  }
  
  /**
   * Adds a condition on a column and returns the records matching the finder
   *
   * @param string $columnName Name of the columns
   * @param mixed $value
   * @param integer $limit Optional maximum number of records to return
   *
   * @return array A list of BaseObject Propel objects
   */
  public function findBy($columnName, $value, $limit = null)
  {
  	$this->criteria->setLimit($limit);
  	$this->criteria->findBy($columnName,$value,$this->connection);
  }
  
   /**
   * Adds a condition on a column and returns the first record matching the finder
   * Useful to retrieve objects by a column which is not the primary key
   *
   * @param string $columnName Name of the columns
   * @param mixed $value
   *
   * @return mixed a BaseObject object or null
   */
  public function findOneBy($columnName, $value)
  {
  	$this->getCriteria()->findOneBy($columnName,$value,$this->connection);
  }
  
  /**
   * Finds record(s) based on one or several primary keys
   * Takes into account hydrating methods previously called on the finder
   *
   * @param mixed $pk A primary kay, a composite primary key, or an array of primary keys
   *
   * @return mixed One or several BaseObject records (based on the input)
   */
  public function findPk($pk)
  {
  	$this->getCriteria()->findPk($pk,$this->connection);
  }
  
   /**
   * Deletes the records found by the finder
   * Beware that behaviors based on hooks in the object's delete() method (such as sfPropelParanoidBehavior)
   * Will only be triggered if you force individual deletes, i.e. if you pass true as first argument
   *
   * @param Boolean $forceIndividualDeletes If false (default), the resulting call is a BasePeer::doDelete(), ortherwise it is a series of delete() calls on all the found objects
   *
   * @return Integer Number of deleted rows
   */
  public function delete($forceIndividualDeletes = false)
  {
  	if($forceIndividualDeletes)
  	{
  		return parent::delete(true);	
  	}
  	return $this->getCriteria()->delete($this->connection);
  }
  
  /**
   * Prepares a pager based on the finder
   * The pager is initialized (it knows how many pages it contains)
   * But it won't be populated until you call getResults() on it
   *
   * @param integer $page The current page (1 by default)
   * @param integer $maxPerPage The maximum number of results per page (10 by default)
   *
   * @return sfPropelFinderPager The initialized pager object
   */
  public function paginate($page = 1, $maxPerPage = 10)
  {
  	$this->getCriteria()->paginate($page,$maxPerPage, $this->connection);
  }
  
  /**
   * Updates the records found by the finder
   * Beware that behaviors based on hooks in the object's save() method
   * Will only be triggered if you force individual saves, i.e. if you pass true as second argument
   *
   * @param Array $values Associative array of keys and values to replace
   * @param Boolean $forceIndividualSaves If false (default), the resulting call is a BasePeer::doUpdate(), ortherwise it is a series of save() calls on all the found objects
   *
   * @return Integer Number of updated rows
   */
  public function set($values, $forceIndividualSaves = false)
  {
  	$this->getCriteria()->update($values, $this->connection,$forceIndividualSaves);
  }
  
  /**
   * Cleans up the query object (if necessary) and logs the latest query
   *
   * @return sfPropelFinder the current finder object
   */
  protected function cleanup()
  {
    if($this->reinit)
    {
      $this->reinitCriteria();
    }
  }
  
  
}