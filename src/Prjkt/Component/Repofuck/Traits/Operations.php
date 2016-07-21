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
}