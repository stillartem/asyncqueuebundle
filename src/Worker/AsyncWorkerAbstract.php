<?php

namespace Drivenow\AsyncWorkersBundle\Worker;

use Drivenow\AsyncWorkersBundle\Exception\MaxAllowedMemoryException;
use Drivenow\AsyncWorkersBundle\Exception\MaxExecutionTimeException;
use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract;
use Drivenow\AsyncWorkersBundle\Repository\TaskRepositoryInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AsyncWorkerAbstract
{
    const MICROSECONDS_IN_ONE_SECONDS = 1000000;

    /** @var string */
    protected $workerId;

    /** @var int */
    private $itemsLimit = 100;

    /** @var int */
    private $memoryLimit;

    /** @var int */
    private $cycles;

    /** @var int */
    private $timeout;

    /** @var int */
    private $executionTimeLimit;

    /** @var bool */
    private $stopWorker = false;

    /** @var SymfonyStyle */
    private $output;

    /** @var int */
    private $shardMax = 1;

    /** @var int */
    private $shardNum = 1;


    public function __construct()
    {
        $this->workerId = $this->generateWorkerId();
        pcntl_signal(SIGTERM, [$this, 'stopWorker']);
    }


    /**
     * @param int $itemsLimit
     *
     * @return self
     */
    public function setItemsLimit($itemsLimit)
    {
        $this->itemsLimit = (int)$itemsLimit;

        return $this;
    }


    /**
     * @return int
     */
    public function getItemsLimit() : int
    {
        return $this->itemsLimit;
    }


    /**
     * @param int $memoryLimit
     *
     * @return self
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;

        return $this;
    }


    /**
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }


    /**
     * @param int $cycles
     *
     * @return self
     */
    public function setCycles($cycles)
    {
        $this->cycles = $cycles;

        return $this;
    }


    /**
     * @return int
     */
    public function getCycles()
    {
        return $this->cycles;
    }


    /**
     * @param SymfonyStyle $out
     *
     * throws \Exception
     */
    final public function execute(SymfonyStyle $out)
    {
        $this->output = $out;
        $iterations = 0;
        $timeStart = new \DateTimeImmutable();

        while ($this->getCycles() > $iterations) {
            $this->debug("starting iteration #" . $iterations);
            $this->run();
            $iterations++;

            if ($out->isVerbose() && !$out->isDebug()) {
                $out->progressAdvance(1);
            }

            pcntl_signal_dispatch();

            if ($this->stopWorker) {
                die("Worker {$this->workerId} has been stopped successfully \n");
            }

            $memoryUsed = memory_get_usage();
            if ($memoryUsed >= $this->getMemoryLimit()) {
                throw MaxAllowedMemoryException::forLimits($this->getMemoryLimit(), $memoryUsed);
            }

            if (new \DateTime() > $timeStart->modify('+ ' . $this->getExecutionTimeLimit())) {
                throw MaxExecutionTimeException::forSeconds($this->getExecutionTimeLimit());
            }

            $this->debug("sleep");
            usleep(self::MICROSECONDS_IN_ONE_SECONDS * $this->getTimeout());
        }
    }


    /**
     * @param TaskEntityAbstract $entity
     *
     * @return int
     */
    protected function lock(TaskEntityAbstract $entity)
    {
        return $this->getRepository()->lock($entity, $this->getWorkerId());
    }


    /**
     * @param TaskEntityAbstract $entity
     */
    protected function markAsDone(TaskEntityAbstract $entity)
    {
        $this->getRepository()->markAsDone($entity);
    }


    /**
     * @param TaskEntityAbstract $entity
     * @param \Throwable $exception
     */
    protected function markAsError(TaskEntityAbstract $entity, \Throwable $exception)
    {
        $this->getRepository()->markAsError($entity, $exception);
    }


    /**
     * @param TaskEntityAbstract $entity
     * @param PostponedException $exception
     */
    protected function markAsPostponed(TaskEntityAbstract $entity, PostponedException $exception)
    {
        $this->getRepository()->markAsPostponed($entity, $exception);
    }


    /**
     * Execute processing items from queue
     *
     * @return  void
     */
    public function run()
    {
        $repo = $this->getRepository();
        $repo->clearMemory();

        $tasks = $repo->getTasks(
            $this->getItemsLimit(),
            $this->getShardMax(),
            $this->getShardNum()
        );
        $this->debug("taken " . count($tasks) . " tasks");

        foreach ($tasks as $task) {
            if ($this->lock($task)) {
                $this->debug("task #" . $task->getId() . " locked");
                try {
                    $this->handle($task);
                    $this->markAsDone($task);
                    $this->debug("task #" . $task->getId() . " done");
                } catch (PostponedException $exception) {
                    $this->debug("task #" . $task->getId() . " postponed");
                    $this->markAsPostponed($task, $exception);
                } catch (\Throwable $exception) {
                    $this->debug("task #" . $task->getId() . " failed: " . $exception->getMessage());
                    $this->logException($exception);
                    $this->markAsError($task, $exception);
                }
            } else {
                $this->debug("failed to lock task #" . $task->getId());
            }
        }
    }


    /**
     * @param int $timeout
     *
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }


    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }


    /**
     * @param int $executionTimeLimit
     *
     * @return self
     */
    public function setExecutionTimeLimit($executionTimeLimit)
    {
        $this->executionTimeLimit = $executionTimeLimit;

        return $this;
    }


    /**
     * @return int
     */
    public function getExecutionTimeLimit()
    {
        return $this->executionTimeLimit;
    }


    /**
     * @return string
     */
    protected function generateWorkerId()
    {
        return gethostname() . '_'
        . substr(strrchr(static::class, "\\"), 1) . '_'
        . uniqid('', false)
        . '_p-' . getmypid();
    }


    /**
     * @return void
     */
    public function stopWorker()
    {
        echo "Start stopping worker {$this->workerId} \n";
        $this->stopWorker = true;

    }


    /**
     * @return string
     */
    protected function getWorkerId()
    {
        return $this->workerId;
    }


    /**
     * @param TaskEntityAbstract $entity
     *
     * @throws PostponedException
     * @throws \Exception
     * @return mixed
     */
    abstract protected function handle(TaskEntityAbstract $entity);


    /**
     * @return TaskRepositoryInterface
     */
    abstract protected function getRepository();

    /**
     * @param \Throwable $exception
     */
    protected function logException(\Throwable $exception)
    {
        if (empty($this->output)) {
            return;
        }

        $this->output->getErrorStyle()->error(
            [
                '[' . $this->getTimestamp() . ']',
                '"' . $exception->getMessage() . '"',
                $exception->getTraceAsString(),
            ]
        );
    }

    /**
     * @param string
     */
    protected function debug($message)
    {
        if (empty($this->output)) {
            return;
        }

        static $start;

        $this->output->writeln(
            sprintf(
                "[%s] %s - %.4f sec",
                $this->getTimestamp(),
                $message,
                microtime(true) - $start
            ),
            SymfonyStyle::VERBOSITY_DEBUG
        );

        $start = microtime(true);
    }

    /**
     * @param int $shardMax
     *
     * @return self
     */
    public function setShardMax($shardMax)
    {
        $this->shardMax = (int)$shardMax;

        return $this;
    }

    /**
     * @param int $shardNum
     *
     * @return self
     */
    public function setShardNum($shardNum)
    {
        $this->shardNum = (int)$shardNum;

        return $this;
    }

    /**
     * @return int
     */
    protected function getShardMax() : int
    {
        return $this->shardMax;
    }

    /**
     * @return int
     */
    private function getShardNum() : int
    {
        return $this->shardNum;
    }

    /**
     * @return string
     */
    protected function getTimestamp() : string
    {
        return (new \DateTime())->format('d.m.Y H:i:s.u');
    }
}