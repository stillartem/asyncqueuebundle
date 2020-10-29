# Async queue bundle
Using this module you can generate and validate workers
# Installation 
To install this package you need to add private repository link to you composer.json

   
    ...
    "repositories": [
       ...
       {"type": "vsc", "url": "https://bitbucket.org/drivenow/asyncqueuebundle.git"}
    ],
    ...
make sure that next to composer.json exist auth.json. Create it if not exist.
Then run composer command
   
   
    composer require drivenow/asyncqueuebundle
# Unit tests execution
```shell
composer install
vendor/phpunit/phpunit/phpunit src/Tests/
```

# Getting started
## To create and run this bundle you should:
- Create worker
- Add this worker to your config.yml (import or directly add)
- create table for worker
- run command metropolis:run-worker-async

#### Create Worker
To create your first worker you should
- Create class ...Worker and extends AsyncWorkerAbstract
```php
class final class ExampleWorker extends AsyncWorkerAbstract {...}
```
- Implements abstract methods
```php
function handle {...}
function getRepository {..}
```
- Setup this worker as service
- Create class ..Entity and extends TaskEntityAbstract
```php
class ExampleTaskEntity extends TaskEntityAbstract {...}
```
- Create class ...Repository and extends:

*In case if you use Docrine* 
```php
class ExampleTaskRepository extends EntityRepository {...}
```

And implement methods

```php
function markAsError{...} 
function markAsDone{...} 
function markAsPostponed{...}
```

 *In case if you dont use Docrine*
```php
class ExampleTaskRepository implements TaskRepositoryInterface {...}
```
And implements all required methods

#### Add worker to config.yml

You should add settings to your config.yml

```php
async_worker:
  workers:
    example:
        configs:
            num_processes: 1
            memory_limit: "256M"
            service_name: "example_worker_service"
            max_exec_time: "1 hours 15 minutes"
            iterations: 1000
            timeout_per_seconds: 2
```

!!! Only service_name is required parameter

#### Create table for worker
- To do : Make symfony script to setup db
####  Run command metropolis:run-worker-async
- Run command 
```shell
app/console metropolis:run-worker-async --help
```
To see all available commands

## Yoy can run workers in 2 diffent ways
- by CLI
- by custom supervisor (To do)
    