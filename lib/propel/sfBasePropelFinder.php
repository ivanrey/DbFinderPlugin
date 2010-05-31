<?php
/*
 * This file is part of the sfPropelFinder package.
 * 
 * (c) 2007 François Zaninotto <francois.zaninotto@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class sfBasePropelFinder extends sfModelFinder
{
	protected 
    $class           = null,
    $peerClass       = null,
    $object          = null,
    $connection      = null,
    $criteria        = null,
    $latestQuery     = '',
    $cache           = null,
    $reinit          = true;
  
  public function getClass()
  {
    return $this->class;
  }

  public function setClass($class, $alias = '')
  {
    $this->class = $class;
    $this->alias = $alias;
    $this->object = new $class();
    $this->peerClass = get_class($this->object->getPeer());
    $this->initialize();
     
    return $this;
  }
    
  public function getConnection()
  {
    if(is_null($this->connection))
    {
      $name = $this->peerClass ? constant($this->peerClass.'::DATABASE_NAME') : '';
      $this->connection = Propel::getConnection($name);
    }
    
    return $this->connection;
  }
  
  public function setConnection($connection)
  {
    $this->connection = $connection;
    
    return $this;
  }
  
  /**
   * Returns the internal query object
   *
   * @return Criteria
   */
  public function getQueryObject()
  {
    return $this->criteria;
  }
  
  
  /**
   * Replaces the internal query object
   *
   * @return sfPropelFinder The current finder
   */
  public function setQueryObject($query)
  {
    $this->criteria = $query;
    
    return $this;
  }
  

  // Finder Initializers
  
  /**
   * Mixed initializer
   * Accepts either a string (Model object class) or an array of model objects
   *
   * @param mixed $from The data to initialize the finder with
   * @param mixed $connection Optional connection object
   *
   * @return sfPropelFinder a finder object
   * @throws Exception If the data is neither a classname nor an array
   */
  public static function from($from, $connection = null)
  {
    if (is_string($from))
    {
      return self::fromClass($from, $connection);
    }
    if (is_array($from) || $from instanceof Doctrine_Collection)
    {
      return self::fromCollection($from);
    }
    throw new Exception('sfPropelFinder::from() only accepts a model object classname or an array of model objects');
  }
  
  /**
   * Array initializer
   *
   * @param array $array Array of Primary keys
   * @param string $class Model classname on which the search will be done
   *
   * @return sfPropelFinder a finder object
   */
  public static function fromArray($array, $class, $pkName)
  {
    $finder = self::fromClass($class);
    $finder->where($pkName, 'in', $array);
    
    return $finder;
  }
  
  /**
   * Class initializer
   *
   * @param string $from Model classname on which the search will be done
   * @param mixed $connection Optional connection object
   *
   * @return sfPropelFinder a finder object
   */
  public static function fromClass($class, $connection = null)
  {
    list($realClass, $alias) = self::getClassAndAlias($class);
    if(is_subclass_of($realClass, 'BaseObject'))
    {
      $me = __CLASS__;
      $finder = new $me($class, $connection);
    }
    else
    {
      throw new Exception('sfPropelFinder::fromClass() only accepts a Propel model classname');
    }
    
    return $finder;
  }
  
  /**
   * Collection initializer
   *
   * @param array $from Array of model objects of the same class
   * @param string $class Optional classname of the desired objects
   * @param string $class Optional column name of the primary key
   *
   * @return sfPropelFinder a finder object
   * @throws Exception If the array is empty, contains not model objects or composite objects
   */
  public static function fromCollection($collection, $class = '', $pkName = '')
  {
    $pks = array();
    foreach($collection as $object)
    {
      if($class != get_class($object))
      {
        if($class)
        {
          throw new Exception('A sfPropelFinder can only be initialized from an array of objects of a single class');
        }
        if($object instanceof BaseObject)
        {
          $class = get_class($object);
        }
        else
        {
          throw new Exception('A sfPropelFinder can only be initialized from an array of Propel objects');
        }
      }
      $pks []= $object->getPrimaryKey();
    }
    if(!$class)
    {
      throw new Exception('A sfPropelFinder cannot be initialized with an empty array');
    }
    $tempObject = new $class();
    foreach ($tempObject->getPeer()->getTableMap()->getColumns() as $column)
    {
      if($column->isPrimaryKey())
      {
        if($pkName)
        {
          throw new Exception('A sfPropelFinder cannot be initialized from an array of objects with several foreign keys');
        }
        else
        {
          $pkName = $column->getPhpName();
        }
      }
    }
    
    return self::fromArray($pks, $class, $pkName);
  }
  

  public function initialize()
  {
    $this->reinitCriteria();
  }
  
  abstract public function reinitCriteria(); 
  
  public function getLatestQuery()
  {
    if(method_exists($this->getConnection(), 'getLastExecutedQuery'))
    {
      return $this->latestQuery;
    }
    else
    {
      throw new RuntimeException('sfPropelFinder::getLatestQuery() only works when debug mode is enabled');
    }
  }
  
   
  public function updateLatestQuery()
  {
    if(method_exists($this->getConnection(), 'getLastExecutedQuery'))
    {
      $this->latestQuery = $this->getConnection()->getLastExecutedQuery();
    }
  }
  
	/**
   * Returns the last record matching the finder
   * Optionally, you can specify the column to sort on
   * If no column is passed, the finder guesses the column to use
   * @see guessOrder()
   *
   * @param string $columnName Optional: The column to order by
   *
   * @return mixed a BaseObject object or null
   */
  public function findLast($columnName = null)
  {
    if($columnName)
    {
      $this->orderBy($columnName, 'desc');
    }
    else
    {
      $this->guessOrder('desc');
    }
    
    return $this->findOne();
  }
 

  /**
   * Returns the first record matching the finder
   * Optionally, you can specify the column to sort on
   * If no column is passed, the finder guesses the column to use
   * @see guessOrder()
   *
   * @param string $columnName Optional: The column to order by
   *
   * @return mixed a BaseObject object or null
   */
  public function findFirst($columnName = null)
  {
    if($columnName)
    {
      $this->orderBy($columnName, 'asc');
    }
    else
    {
      $this->guessOrder('asc');
    }
    
    return $this->findOne();
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
    $deleteCriteria = $this->getCriteria();
    if($forceIndividualDeletes)
    {
      $objects = $this->find();
      foreach($objects as $object)
      {
        $object->delete($this->getConnection());
      }
      $ret = count($objects);
    }
    else
    {
      if($deleteCriteria->equals(new Criteria()))
      {
        // doDelete will delete nothing when passed an empty criteria
        // while it should, in fact, delete all
        $deleteCriteria = $this->addTrueCondition($deleteCriteria);
      }
      $ret = call_user_func(array($this->peerClass, 'doDelete'), $deleteCriteria, $this->getConnection());
    }
    $this->cleanup();
    
    return $ret;
  }
}