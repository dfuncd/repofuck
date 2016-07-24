<?php

namespace Prjkt\Component\Repofuck\Containers\Repositories;


use Prjkt\Component\Repofuck\{
	Exceptions\ResourceNotDefined,
	Traits\Operations
};

class Repositories
{
	use Operations;

	/**
	 * Repository pointer
	 *
	 * @var \Prjkt\Component\Repofuck\Repofuck
	 */
	protected $repository;

	/**
	 * Repositories array container
	 *
	 * @var array
	 */
	protected $repositories = [];

	/**
	 * Returns the persisted repository
	 *
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function current() : \Prjkt\Component\Repofuck\Repofuck
	{
		return $this->repository;
	}

	/**
	 * Checks the repositories container if a repository exists
	 * ~ defer to first configured if none provided
	 *
	 * @param string $repository
	 * @return bool
	 */
	public function has(string $repository = null) : bool
	{
		if ( is_null($repository) ) {
			return $this->isRepofuck($this->repository) ? true : false;
		}

		return array_key_exists($repository, $this->repositories) ? true : false;
	}

	/**
	 * Pushes the repository to the container
	 *
	 * @param \Prjkt\Component\Repofuck\Repofuck $repository
	 * @return \Prjkt\Component\Repofuck\Containers\Repositories
	 */
	public function push(\Prjkt\Component\Repofuck\Repofuck $repository) : \Prjkt\Component\Repofuck\Containers\Repositories
	{
		$this->repositories[$this->resolveRepoName($repository)] = $repository;

		return $this;
	}

	/**
	 * Resolves the entity given by its name
	 *
	 * @param string $repository
	 * @throws \Prjkt\Component\Repofuck\Exceptions\RepositoryNotDefined
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	public function resolve(string $repository) : \Prjkt\Component\Repofuck\Repofuck
	{
		if ( ! array_key_exists($repository, $this->repositories) ) {
			throw new RepositoryNotDefined;
		}

		return $this->repositories[$repository];
	}

	/**
	 * Sets a repository pointer
	 *
	 * @param \Prjkt\Component\Repofuck\Repofuck $repository
	 * @return \Prjkt\Component\Repofuck\Repofuck
	 */
	protected function set(\Prjkt\Component\Repofuck\Repofuck $repository) : \Prjkt\Component\Repofuck\Containers\Repositories
	{
		$this->repository = $repository;

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
