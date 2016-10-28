<?php

namespace Prjkt\Component\Repofuck;

use Closure;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\{
	Model,
	Collection,
	Builder,
	ModelNotFoundException
};

use Prjkt\Component\Repofuck\Exceptions\{
	EntityNotDefined,
	ResourceNotFound,
	InvalidCallback,
	InvalidCallbackReturn
};

abstract class Repofuck
{
	use Traits\Operations;

	/**
	 * Laravel's App instance
	 *
	 * @var \Illuminate\Container\Container $app
	 */
	protected $app;

	/**
	 * The current persisted entity
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	public $entity;

	/**
	 * Entities container
	 *
	 * @var \Prjkt\Component\Repofuck\Containers\Entities
	 */
	public $entities;

	/**
	 * Repositories container
	 *
	 * @var \Prjkt\Component\Repofuck\Containers\Repositories
	 */
	public $repositories;

	/**
	 * Resources
	 *
	 * @var array
	 */
	protected $resources = [];

	/**
	 * Data
	 * 
	 * @var array
	 */
	protected $data = [];

	/**
	 * Keys
	 *
	 * @var array
	 */
	protected $keys = [];

	/**
	 * Columns to query
	 *
	 * @var array
	 */
	protected $columns = ['*'];

	/**
	 * Class constructor
	 *
	 * @param \Illuminate\Container\Container $app
	 */
	public function __construct(Container $app = null)
	{
		$this->app = is_null($app) ? new Container : $app;

		$this->loadContainers();

		$this->loadResources();
	}

	/**
	 * Loads the entities and repositories containers
	 *
	 */
	public function loadContainers()
	{
		$this->entities = new \Prjkt\Component\Repofuck\Containers\Entities;
		$this->repositories = new \Prjkt\Component\Repofuck\Containers\Repositories;
	}

	/**
	 * Loads all resources for the repository to use
	 *
	 */
	protected function loadResources()
	{
		if ( $this->hasValues($this->resources) ) {
			array_walk($this->resources, [$this, 'register']);
		}
	}

	/**
	 * Registers an entity/repository to their appropriate containers
	 *
	 * @param string|object $instance
	 * @throws \Prjkt\Component\Repofuck\Exceptions\ResourceNotAnObject
	 * @return true
	 */
	public function register($instance) : bool
	{
		if ( ! is_object($instance) ) {
			$instance = $this->app->make($instance);
		}

		switch($instance)
		{
			// Adds the entity instance to the entities property
			case ( $instance instanceof Model ):

				$this->entities->push($instance);

			break;

			// Adds the repository instance to the repositories property
			case ( $instance instanceof Repofuck ):

				$this->repositories->push($instance);

			break;
		}

		// If the entity property has not yet defined, set it with first configured entity
		if ( ! is_object($this->entities->has()) ) {
			$this->entities->set($this->entities->resolve(null, $this->resolveRepoName($this)));
			$this->entity = $this->entities->current();
		}

		return true;
	}

	/**
	 * Sets the entity to be chained
	 *
	 * @param string $name
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function entity(string $name) : \Prjkt\Component\Repofuck\Repofuck
	{
		$this->entity = $this->entities->resolve($name);

		return $this;
	}

	/**
	 * [Deprecated] Resets the entity
	 *
	 * @param string $name [def=null]
	 */
	public function resetEntity(string $name = null) :\Prjkt\Component\Repofuck\Repofuck
	{
		return $this->entity($name);
	}

	/**
	 * Set the data and keys for the repository
	 *
	 * @param array $parameters
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function setDataAndKeys(array $parameters) : \Prjkt\Component\Repofuck\Repofuck
	{
		$keys = array_keys($parameters);

		$this->setKeys($keys)->setData($parameters);

		return $this;
	}

	/**
	 * Set the columns to be queried
	 *
	 * @param array $columns
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function setColumns(array $columns) : \Prjkt\Component\Repofuck\Repofuck
	{
		$this->columns = $columns;

		return $this;
	}

	/**
	 * Set the data for the repository
	 *
	 * @param array
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function setData(array $data) : \Prjkt\Component\Repofuck\Repofuck
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * Set the keys for the repository
	 *
	 * @param array
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function setKeys(array $keys) : \Prjkt\Component\Repofuck\Repofuck
	{
		$this->keys = $keys;

		return $this;
	}

	/**
	 * Get the columns
	 *
	 * @return array
	 */
	public function getColumns() : array
	{
		return $this->columns;
	}

	/**
	 * Get the data in the repository
	 *
	 * @return array
	 */
	public function getData() : array
	{
		return $this->data;
	}

	/**
	 * Get the keys in the repository
	 *
	 * @return array
	 */
	public function getKeys() : array
	{
		return $this->keys;
	}

	/**
	 * Finds an entity by its ID
	 *
	 * @param string $id
	 * @return Object $entity
	 */
	public function find($id) : Model
	{
		return $this->entity->find($id);
	}

	/**
	 * Finds the first entity by the given parameters
	 *
	 * @param integer|array|string
	 * @param string
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function first($params, $value = null)
	{
		try {
			switch ($params)
			{
				case ( is_numeric($params) ):

					$entity = $this->entity->findOrFail($params);

				break;

				case ( is_array($params) ):

					$params = ! $this->hasValues($params) ? $this->getData() : $params;

					$entity = $this->entity->where($params)->firstOrFail($this->getColumns());

				break;

				case ( is_string($params) && ! is_null($value) ):

					$entity = $this->entity->where($params, $value)->firstOrFail();

				break;
			}
		} catch ( ModelNotFoundException $e ) {
			return false;
		}

		return $entity;
	}

	/**
	 * Prepares the persistence of a repository or entity
	 *
	 * @param \Closure $function
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function prepare(Closure $function) : \Prjkt\Component\Repofuck\Repofuck
	{
		$return = call_user_func_array($function, [($this)->resetEntity()]);

		switch($return)
		{
			case null:

				return $this;

			break;
			
			case ( $return instanceof Builder or $return instanceof Model ):

				// This will persist the entity throughout the repository for the next operation
				$this->entity = $return;

			break;

			case ( $return instanceof \Prjkt\Component\Repofuck\Repofuck && $return instanceof $this ):

				// Returns a modified persistence of itself where operations are contained
				return $return;

			break;

			case ( $return instanceof \Prjkt\Component\Repofuck\Repofuck && ! $return instanceof $this ):

				// This will persist the repository for the next operation
				// It also gives an advantage as the repository contained
				$this->repositories->set($return);

				return $this->repositories->resolve();

			break;

			case ( is_array($return) ):

				// This will persist the keys and data returned
				$this->setDataAndKeys($return);

			break;
		}

		// If there's a repository being persisted, return it, defer to self when there's none
		return $this;
	}

	/**
	 * Gets an entity by parameters
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function get() : Collection
	{
		return $this->entity->get();
	}

	/**
	 * Creates a new model
	 *
	 * @return \Illuminate\Database\Eloquent\Model $entity
	 */
	public function create() : Model
	{
		$entity = $this->map($this->getData(), $this->getkeys());
		$entity->save();

		return $entity;
	}

	/**
	 * Updates the entity
	 *
	 * @return \Illuminate\Database\Eloquent\Model $entity
	 */
	public function update($identifier) : Model
	{
		$entity = $this->map($this->getData(), $this->getkeys(), $this->first($identifier));
		$entity->save();

		return $entity;
	}

	/**
	 * Deletes the entity
	 *
	 * @return boolean
	 */
	public function delete($identifier) : bool
	{
		$entity = $this->first($identifier);

		if ( is_null($entity) ) {
			return false;
		}

		$entity->delete();

		return true;
	}

	/**
	 * Mass assignment
	 *
	 * @param array $inserts
	 * @param array $keys
	 * @param \Illuminate\Database\Eloquent\Model $entity
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function map(array $inserts, array $keys = [], Model $entity = null) : Model
	{
		$entity = is_null($entity) ? $this->entity : $entity;

		if ( $this->hasValues($keys) ) {

			foreach($inserts as $key => $val)
			{
				if ( ! in_array($key, $keys) ) {
					break;
				}

				$entity->{$key} = $val;
			}

			return $entity;
		}

		$entity = $entity->fill($inserts);

		return $entity;
	}

	/**
     * Sets the where clause to the current entity. 
     *
     * @param Closure $query
     * @param bool $append
     * @return \Prjkt\Component\Repofuck\Repofuck
     */
	public function where(Closure $query, $append = false)
	{
		$entity = $this->entity;

		// Overwrite: Strip down the Builder to a Model when 
		// the current entity has a where clause and $append is false 
		if ( ! $append && $entity instanceof Builder ) {
			$entity = $this->entity->getModel();
		}

		// Supplied callback return to be attached
		try	{

			$entity = call_user_func($query, $entity);

			if ( ! $entity instanceof Builder ) {
				throw new InvalidCallbackReturn;
			}

		} catch (InvalidCallbackReturn $e) {

			//Retain the current entity as it was before this method's call.
			$entity = $this->entity;

		}

		// Assign the entity with the newly attached where clause
		$this->entity = $entity;

		return $this;
	}

	/**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
	public function __get($key)
	{
		return $this->app->{$key};
	}

}
