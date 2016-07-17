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
	ResourceNotFound
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
	protected function setEntity(string $entity) : \Prjkt\Component\Repofuck\Repofuck
	{
		$this->entity = $this->entity($entity);

		return $this;
	}

	/**
	 * Returns the {first configured|inputted} entity
	 *
	 * @param string $entity
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return \Illuminate\Eloquent\Model
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
	 * @param array $functions
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function prepare(array $functions) : \Prjkt\Component\Repofuck\Repofuck
	{
		$entity = $this->entity;

		foreach($functions as $function => $functionParams) {
			$entity = call_user_func_array([$entity, $function], [$functionParams]);
		}

		$this->setEntity($entity); // Persist the entity

		return $this;
	}

	/**
	 * Gets an entity by parameters
	 *
	 * @param array $params
	 * @return array
	 */
	public function get(array $params = [], Closure $callback = null) : Collection
	{
		$params = $callback instanceof Closure ? $this->executeCallback($callback, $params) : $params;

		return $this->entity->where($params)->get();
	}

	/**
	 * Creates a new model
	 *
	 * @param array $data
	 * @param array $keys
	 * @return \Illuminate\Eloquent\Model $entity
	 */
	public function create(array $data, array $keys = [], Closure $callback = null) : Model
	{
		$data = $callback instanceof Closure ? $this->executeCallback($callback, $data) : $data;

		$entity = $this->map($data, $keys, (new $this->entity));
		$entity->save();

		return $entity;
	}

	/**
	 * Updates the entity
	 *
	 * @param array $data
	 * @param integer|array $identifier
	 * @param array $keys
	 * @return \Illuminate\Eloquent\Model $entity
	 */
	public function update(array $data, $identifier, array $keys = [], Closure $callback = null) : Model
	{
		$data = $callback instanceof Closure ? $this->executeCallback($callback, $data) : $data;

		$entity = $this->entity->first($identifier);
		$entity = $this->map($data, $keys);
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
	 * @return \Illuminate\Eloquent\Model
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
