<?php

namespace Prjk\Component\Repofuck;

class Repofuck
{

	/**
	 * Laravel's App instance
	 *
	 * @var \Illuminate\Foundation\Application $app
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
	protected $entities;

	/**
	 * Repositories container
	 *
	 * @var array
	 */
	protected $repositories;

	/**
	 * Class constructor
	 *
	 * @param \Illuminate\Foundation\Application $app
	 */
	public function __construct(\Illuminate\Foundation\Application $app)
	{

	}

	/**
	 * Loads entities from an array
	 *
	 * @param array $resources
	 * @return true
	 */
	public function loadResources(array $resources) : bool
	{
		array_walk($resources, [$this, 'register']);

		return true;
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
			$instance = new $instance;
		}

		if ( $instance instanceof \Illuminate\Eloquent\Model ) {
			$this->entities[$instance->getTable()] = $instance;
		}

		if ( $instance instanceof Repofuck) {
			$repositoryName = strtolower(str_replace('Repository', '', $instance));
			$this->repositories[$repositoryName] = $instance;
		}

		$this->entity = $this->setEntity($this->entity());

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
	 * @return $entity
	 */
	public function entity($entity = null) : \Illuminate\Eloquent\Model
	{
		if ( ! count($this->entities) > 0 && $entity === null ) {
			$entityName = strtolower(str_replace('Repository', '', get_class()));

			return array_key_exists($entityName, $this->entities) ?
				$this->entities[$entityName]:
				$this->entities[0];
		}

		if ( array_key_exists($entity, $this->entities) ) {
			return $this->entity = $this->entities[$entity];
		}

		throw new Exceptions\EntityNotDefined($e);
	}

	/**
	 * Finds an entity by its ID
	 *
	 * @param string $id
	 * @return Object $entity
	 */
	public function find($id) : \Illuminate\Eloquent\Model
	{
		return $this->entity->find($id);
	}

	/**
	 * Finds the first entity by the given parameters
	 *
	 * @param integer|array|string $param
	 * @return Object $entity
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
	public function get(array $params)
	{
		return $this->entity->where($params)->get();
	}

	/**
	 * Creates a new model
	 *
	 * @param array $data
	 * @return Object $entity
	 */
	public function create(array $data) : \Illuminate\Eloquent\Model
	{
		$entity = $this->map($data, (new $this->entity))->save();

		return $this->entity;
	}

	/**
	 * Updates the entity
	 *
	 * @param array $data
	 * @param integer|array $identifier
	 * @return Object $entity
	 */
	public function update(array $data, $identifier)
	{
		$entity = $this->entity->first($identifier);
		$entity = $this->map($data)->save();

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
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return object
	 */
	protected function map(array $inserts, $entity = null) : \Illuminate\Eloquent\Model
	{
		$entity = ! is_null($entity) ? $entity : $this->entity;

		if ( ! is_object($this->entity) ) {
			throw new Exceptions\EntityNotDefined;
		}

		foreach($inserts as $key => $val)
		{
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