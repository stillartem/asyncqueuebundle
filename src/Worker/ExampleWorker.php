<?php

namespace Drivenow\AsyncWorkersBundle\Worker;


use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract;
use Drivenow\AsyncWorkersBundle\Repository\ExampleTaskRepository;
use Drivenow\AsyncWorkersBundle\Repository\TaskRepositoryInterface;

class ExampleWorker extends AsyncWorkerAbstract
{
    /** @var ExampleTaskRepository */
    private $exampleTaskRepository;


    /**
     * ExampleWorker constructor.
     *
     * @param ExampleTaskRepository $exampleTaskRepository
     */
    public function __construct(ExampleTaskRepository $exampleTaskRepository)
    {
        parent::__construct();
        $this->exampleTaskRepository = $exampleTaskRepository;
    }


    /**
     * @param TaskEntityAbstract $item
     *
     * @return int
     * @throws \Exception
     */
    protected function handle(TaskEntityAbstract $item)
    {
        $rand = mt_rand(0, 2);
        if ($rand === 1) {
            return 1;
        }

        if ($rand === 2) {
            throw new \Exception('Some error');
        }

        throw PostponedException::create(new \DateTime('+ 20 sec'),new \Exception('Some exception'));
    }


    /**
     * @return TaskRepositoryInterface
     */
    public function getRepository()
    {
        return $this->exampleTaskRepository;
    }


}