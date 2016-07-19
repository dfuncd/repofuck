# Repofuck

Fucking with the repository design pattern

### Why?

Writing the same base repository class is tiring as fuck. Better to write my own repository package to sustain my need. There is however a far more complex repository package called "[Repository](https://github.com/rinvex/repository)". The major difference between the two is that you can contain and call repositories and models magically, this feature still needs work in which in itself will make the package having a different goal.

### Prerequisites
* PHP v7
* Illuminate v5.1 or v5.2 or v5.3
	* Support
	* Database
	* Container
	* Contracts