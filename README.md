# Repofuck

Fucking with the repository design pattern

### Why?

Repofuck is dynamically persistent repository provider that also acts as a factory in runtime. Business logic can be written in the usual way or in closures if additional operations are needed before the data is given out. This eliminates the backdrop of needing predefined repository functions and replacing it by the entities themselves. Repofuck also features a dynamic mass assignment workflow where we can leverage predefined keys from Eloquent's `getFillable` or assign our own keys to be persisted in the repository. This way, when we're about to save the entity. The data itself is already persisted and can be manipulated on the fly for additional operations.


### Sample Usage
```php
$data = $repo->prepare(function ($r, $q)
{
	//.. operations here
	return $r->entities->current()
		->with('relationship')
		->where($q['where']);
	
}, [
	'where' => [
		['foo', 'LIKE', '%BAR%']
	]
])->get();
```
As you can see above. We are fetching data with the parameters with a relationship. The repository will be persisting the return of the callback and we can perform another operation. In which case we are performing a get.


### Prerequisites
* PHP v7
* Illuminate v5.1 or v5.2 or v5.3
	* Support
	* Database
	* Container
	* Contracts
