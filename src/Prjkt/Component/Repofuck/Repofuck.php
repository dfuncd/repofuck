<?php

namespace Prjkt\Component\Repofuck;

use Illuminate\{
	Container\Container,
	Database\Eloquent\Model,
	Database\Eloquent\Collection
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

		if ( $instance instanceof Model ) {
			$this->entities[$instance->getTable()] = $instance;
		}

		if ( $instance instanceof Repofuck) {
			$repositoryName = $this->resolveRepoName();
			$this->repositories[$repositoryName] = $instance;
		}

		if ( ! is_object($this->entity) ) {
			$this->setEntity($this->entity());
		}

		return true;
	}

	/**
	 * Sets the current entity
	 *
	 * @param Object $entity
	 * @return void
	 */
	protected function setEntity($entity)
	{
		$this->entity = $entity;
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
		if ( is_numeric($params) ) {
			$entity = $this->entity->find($id);
		}

		if ( is_array($params) ) {
			$entity = $this->entity->where($params)->first();
		}

		if ( is_string($params) ) {
			$entity = $this->entity->where($params, $value)->first();
		}

		return $entity;
	}

	/**
	 * Gets an entity by parameters
	 *
	 * @param array $params
	 * @return array
	 */
	public function get(array $params = []) : Collection
	{
		return $this->entity->where($params)->get();
	}

	/**
	 * Creates a new model
	 *
	 * @param array $data
	 * @param array $keys
	 * @return \Illuminate\Eloquent\Model $entity
	 */
	public function create(array $data, array $keys = []) : Model
	{
		$entity = $this->map($data, $keys, (new $this->entity))->save();

		return $this->entity;
	}

	/**
	 * Updates the entity
	 *
	 * @param array $data
	 * @param integer|array $identifier
	 * @param array $keys
	 * @return \Illuminate\Eloquent\Model $entity
	 */
	public function update(array $data, $identifier, array $keys = []) : Model
	{
		$entity = $this->entity->first($identifier);
		$entity = $this->map($data, $keys)->save();

		return $this->entity;
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
	protected function map(array $inserts, array $keys = [], $entity = null) : Model
	{
		$entity = ! is_null($entity) ? $entity : $this->entity;

		if ( ! is_object($this->entity) ) {
			throw new Exceptions\EntityNotDefined;
		}

		foreach($inserts as $key => $val)
		{
			if ( count($keys) > 0 && ! in_array($keys, $key) ) {
				break;
			}

			$entity->{$key} = $val;
		}

		return $entity;
	}

	/**
	 * Mimics the original behavior of the DI
	 *
	 * @return Object
	 */
	public function __get($key)
	{
		try {
			if (array_key_exists($key, $this->entities)) return $this->entities[$key];
			if (array_key_exists($key, $this->repositories)) return $this->repositories[$key];

			throw new Exceptions\ResourceNotFound;
		} catch (Exceptions\ResourceNotFound $e) {

		}
	}

}