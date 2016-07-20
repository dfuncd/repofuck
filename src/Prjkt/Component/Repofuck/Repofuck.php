<?php

namespace Prjkt\Component\Repofuck;

use Closure;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\{
	Model,
	Collection,
	Builder
};

use Exceptions\{
	EntityNotDefined,
	ResourceNotFound,
	InvalidCallback,
	InvalidCallbackReturn
};

abstract class Repofuck
{

	/**
	 * Laravel's App instance
	 *
	 * @var \Illuminate\Container\Container $app
	 */
	protected $app;

	/**
	 * The current entity pointer
	 *
	 * @var object
	 */
	protected $entity;

	/**
	 * Entities container
	 *
	 * @var array
	 */
	protected $entities = [];

	/**
	 * Repositories container
	 *
	 * @var array
	 */
	protected $repositories = [];

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
	 * Class constructor
	 *
	 * @param \Illuminate\Container\Container $app
	 */
	public function __construct(Container $app)
	{
		$this->app = $app;

		$this->loadResources();
	}

	/**
	 * Loads all resources for the repository to use
	 *
	 * @return true
	 */
	public function loadResources() : bool
	{
		if ( count($this->resources) > 0 ) {
			array_walk($this->resources, [$this, 'register']);
		}

		return true;
	}

	/**
	 * Resolves the name of the repository
	 *
	 * @return string
	 */
	protected function resolveRepoName()
	{
		return strtolower(str_replace('Repository', '', (new \ReflectionClass($this))->getShortName()));
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

		switch($instance) {

			// Adds the entity instance to the entities property
			case ($instance instanceof Model):
				$this->entities[$instance->getTable()] = $instance;
			break;

			// Adds the repository instance to the repositories property
			case ($instance instanceof Repofuck):
				$this->repositories[$this->resolveRepoName()] = $instance;
			break;

		}

		// If the entity property has not yet defined, set it with first configured entity
		if ( ! is_object($this->entity) ) {
			$this->setEntity($this->entity());
		}

		return true;
	}

	/**
	 * Sets the current entity
	 *
	 * @param string $entity
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	protected function setEntity($entity) : \Prjkt\Component\Repofuck\Repofuck
	{
		switch($entity)
		{
			case $entity instanceof Model || $entity instanceof Builder:
				$this->entity = $entity;
			break;
				
			case is_string($entity):
				$this->entity = $this->entity($entity);
			break;
		}

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
	 * Returns the {first configured|inputted} entity
	 *
	 * @param string $entity
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function entity(string $entity = null) : Model
	{
		if ( count($this->entities) > 0 && $entity === null ) {
			$parsedName = $this->resolveRepoName();

			return array_key_exists($parsedName, $this->entities) ?
				$this->entities[$parsedName]:
				array_values($this->entities)[0];
		}

		if ( array_key_exists($entity, $this->entities) ) {
			return $this->entity = $this->entities[$entity];
		}

		throw new Exceptions\EntityNotDefined;
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
	 * @param integer|array|string $param
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
	 */
	public function first($params, $value = null)
	{
		switch ($params) {

			case is_numeric($params):

				$entity = $this->entity->find($id);

			break;

			case is_array($params):

				$entity = $this->entity->where($params)->first();

			break;

			case is_string($params):

				$entity = $this->entity->where($params, $value)->first();

			break;

		}

		return $entity;
	}

	/**
	 * Prepares the entity
	 *
	 * @param array $parameters
	 * @param \Closure $function [default=null]
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function prepare(array $parameters, Closure $function = null) : \Prjkt\Component\Repofuck\Repofuck
	{
		$parameters = ! count($parameters) > 0 ? $this->getData() : $parameters;

		if ( $function instanceof Closure ) {

			$return = call_user_func_array($function, [$parameters, $this]);

			switch($return)
			{
				case $return instanceof Builder:

					// This will persist the entity throughout the repository for the next operation
					$this->setEntity($return);

				break;

				case $return instanceof \Prjkt\Component\Repofuck\Repofuck:

					// This will persist the repository for the next operation
					// It also gives an advantage as the repository contained
					// $this->setRepository($return);

				break;

				case is_array($return):

					// This will persist the keys and data returned
					// $this->setDataAndKeys($return);

				break;

				case 'default':
					
					// do nothing

				break;
			}

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
	 * @param integer|array $identifier
	 * @return \Illuminate\Database\Eloquent\Model $entity
	 */
	public function update($identifier) : Model
	{
		$entity = $this->entity->first($identifier);
		$entity = $this->map($this->getData(), $this->getkeys());
		$entity->save();

		return $entity;
	}

	/**
	 * Deletes the entity
	 *
	 * @param integer|array $identifier
	 * @return boolean
	 */
	public function delete($identifier) : bool
	{
		$entity = $this->entity->first($identifier);

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
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function map(array $inserts, array $keys = []) : Model
	{
		$entity = $this->entity;

		if ( count($keys) > 0 ) {

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
	 * Executes the callback
	 *
	 * @param array $data
	 * @param Closure $callback
	 * @return array
	 */
	protected function executeCallback(Closure $callback, array $data) : array
	{
		return call_user_func_array($callback, [$this, $data]);
	}

	/**
	 * Mimics the original behavior of the DI
	 *
	 * @return Object
	 */
	public function __get($key)
	{
		switch($key) {

			case array_key_exists($key, $this->entities):
				return $this->entities[$key];
			break;

			case array_key_exists($key, $this->repositories):
				return $this->repositories[$key];
			break;

		}

		throw new ResourceNotFound;
	}

}
