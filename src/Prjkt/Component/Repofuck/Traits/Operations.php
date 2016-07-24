<?php

namespace Prjkt\Component\Repofuck\Repofuck\Traits;

trait Operations
{
	/**
	 * Check whether an array has values
	 *
	 * @param array $array
	 * @return bool
	 */
	public function hasValues(array $array) : bool
	{
		return count($array) > 0 ? true : false;
	}

	/**
	 * Checks if the value given is an instance of Repofuck
	 *
	 * @param mixed
	 * @return bool
	 */
	public function isRepofuck($value) : bool
	{
		return is_object($value) && $value instanceof \Prjkt\Component\Repofuck\Repofuck ? true : false;
	}

	/**
	 * Resolves the name of the repository
	 *
	 * @return string
	 */
	public function resolveRepoName(\Prjkt\Component\Repofuck\Repofuck $repository) : string
	{
		return strtolower(str_replace('Repository', '', (new \ReflectionClass($repository))->getShortName()));
	}
}