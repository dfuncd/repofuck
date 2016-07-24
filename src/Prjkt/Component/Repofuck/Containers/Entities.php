<?php

namespace Prjkt\Component\Repofuck\Repofuck\Containers;

use Illuminate\Database\Eloquent\{
	Model,
	Builder
};

use Prjkt\Component\Repofuck\{
	Exceptions\ResourceNotFound,
	Traits\Operations
};

class Entities
{
	use Operations;
	
	/**
	 * Entity pointer
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $entity;

	/**
	 * Entities array container
	 *
	 * @var array
	 */
	protected $entities = [];

	/**
	 * Get the current entity pointer
	 *
	 * @param \Illuminate\Database\Eloquent\Model
	 */
	public function current()
	{
		return $this->entity;
	}

	/**
	 * Checks the entities container if an entity exists
	 * ~ defer to first configured if none provided
	 *
	 * @param string $entity
	 * @return bool
	 */
	public function has(string $entity = null) : bool
	{
		if ( is_null($entity) ) {
			return $this->isEloquent($this->entity) ? true : false;
		}

		return array_key_exists($entity, $this->entities) ? true : false;
	}

	/**
	 * Adds the entity to the container
	 *
	 * @param \Illuminate\Database\Eloquent\Model
	 * @return \Prjkt\Component\Repofuck\Containers\Entities
	 */
	public function push(Model $entity)
	{
		$this->entities[$entity->getTable()] = $entity;

		return $this;
	}

	/**
	 * Resolves the entity given by its table name or the first one configured
	 *
	 * @param string $entity
	 * @throws \Prjkt\Component\Repofuck\Exceptions\EntityNotDefined
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function resolve(string $entity = null) : Model
	{
		switch($entities)
		{
			case ($this->hasValues($this->entities) && $entity === null):
				return array_key_exists($parsedName, $this->entities) ?
					$this->entities[$this->resolveRepoName()] : array_values($this->entities)[0];
			break;

			case array_key_exists($entity, $this->entities):
				return $this->entities[$entity];
			break;
		}

		throw new Exceptions\EntityNotDefined;
	}

	/**
	 * Sets the current entity
	 *
	 * @param mixed $entity
	 * @return \Prjkt\Component\Repofuck\Containers\Entities
	 */
	public function set($entity) : \Prjkt\Component\Repofuck\Containers\Entities
	{
		switch($entity)
		{
			case $entity instanceof Model || $entity instanceof Builder:

				$this->entity = $entity;

			break;
				
			case is_string($entity):

				$this->entity = $this->resolve($entity);

			break;
		}

		return $this;
	}

	/**
	 * Mimics the original behavior of the DI
	 *
	 * @return Object
	 */
	public function __get($key)
	{
		return $this->resolve($key);
	}
}
