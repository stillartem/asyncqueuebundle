<?php

namespace Drivenow\AsyncWorkersBundle\Command;


use Drivenow\AsyncWorkersBundle\Exception\WorkerLimitExceptionInterface;
use Drivenow\AsyncWorkersBundle\Worker\AsyncWorkerAbstract;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunAsyncWorkersCommand extends ContainerAwareCommand
{
    /** @var SymfonyStyle */
    protected $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('metropolis:run-worker-async')
            ->setDescription('Run worker in async process')
            ->addOption('worker', null, InputOption::VALUE_REQUIRED, 'Identification of worker')
            ->addOption('max-execution-time', null, InputOption::VALUE_REQUIRED, 'Maximum time for execution')
            ->addOption('iterations', null, InputOption::VALUE_REQUIRED, 'Count of iterations before die')
            ->addOption('max-memory-usage', null, InputOption::VALUE_REQUIRED, 'Memory limit', '256MB')
            ->addOption('per-select', null, InputOption::VALUE_REQUIRED, 'Count of items per 1 transaction')
            ->addOption('timeout-per-seconds', null, InputOption::VALUE_REQUIRED, 'Timeout after each iteration')
            ->addOption('shard-max', null, InputOption::VALUE_REQUIRED, 'Maximum shard number')
            ->addOption('shard-num', null, InputOption::VALUE_REQUIRED, 'Exact shard number');
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $arguments = $this->retrieveArguments($input);

        $worker = $this->getWorker($arguments['service']);

        $worker->setItemsLimit($arguments['per-select'])
            ->setMemoryLimit($this->convertToBytes($arguments['max-memory-usage']))
            ->setTimeout($arguments['timeout-per-seconds'])
            ->setCycles($arguments['iterations'])
            ->setExecutionTimeLimit($arguments['max-execution-time'])
            ->setShardMax($arguments['shard-max'])
            ->setShardNum($arguments['shard-num']);

        $this->displayStart($arguments, $worker);

        try {
            $worker->execute($this->io);
        } catch (WorkerLimitExceptionInterface $e) {
            $this->displayNote($e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->displayError($e->getMessage());

            return;
        }

        $this->displayFinish();
    }


    /**
     * @param string $from
     *
     * @return int
     */
    private function convertToBytes($from)
    {
        $number = substr($from, 0, -2);
        switch (strtoupper(substr($from, -2))) {
            case "KB":
                return $number * 1024;
            case "MB":
                return $number * (1024 ** 2);
            case "GB":
                return $number * (1024 ** 3);
            case "TB":
                return $number * (1024 ** 4);
            case "PB":
                return $number * (1024 ** 5);
            default:
                return $from;
        }
    }


    /**
     * @param InputInterface $input
     *
     * @return array
     */
    private function retrieveArguments(InputInterface $input)
    {
        $workers = $this->getContainer()->getParameter('async_workers');
        $worker = $input->getOption('worker');


        if ($worker === null || !isset($workers[$worker]['service_name'])
            || !$this->getContainer()->has(
                $workers[$worker]['service_name']
            )
        ) {
            throw new \RuntimeException('The "--worker" option is invalid');
        }

        $allowedArguments = [
            'max-execution-time'  => [
                'command' => 'max-execution-time',
                'config'  => 'max_execution_time',
                'default' => '1 day',
            ],
            'per-select'          => [
                'command' => 'per-select',
                'config'  => 'per_select',
                'default' => 10
            ],
            'iterations'          => [
                'command'                => 'iterations',
                'config'                 => 'iterations',
                '
                default' => 100,
            ],
            'max-memory-usage'    => [
                'command' => 'max-memory-usage',
                'config'  => 'max_memory_usage',
                'default' => '512MB',
            ],
            'timeout-per-seconds' => [
                'command' => 'timeout-per-seconds',
                'config'  => 'timeout_per_seconds',
                'default' => 1,
            ],
            'shard-max'           => [
                'command' => 'shard-max',
                'config'  => 'shard_max',
                'default' => 1,
            ],
            'shard-num'           => [
                'command' => 'shard-num',
                'config'  => 'shard_num',
                'default' => 1,
            ],
        ];

        $retrievedArguments = ['service' => $workers[$worker]['service_name']];
        $taskConfiguration = $workers[$worker];

        foreach ($allowedArguments as $key => $value) {
            if ($input->getOption($value['command']) != null && $input->getOption($value['command']) !== 'null') {
                $retrievedArguments[$key] = $input->getOption($value['command']);
            } elseif (isset($taskConfiguration[$value['config']])) {
                $retrievedArguments[$key] = $taskConfiguration[$value['config']];
            } else {
                $retrievedArguments[$key] = $value['default'];
            }
        }

        return $retrievedArguments;
    }


    /**
     * @param array $arguments
     * @param AsyncWorkerAbstract $worker
     */
    protected function displayStart(array $arguments, AsyncWorkerAbstract $worker)
    {
        if (!$this->io->isVerbose()) {
            return;
        }

        $this->io->note(sprintf("Starting worker '%s'", get_class($worker)));

        $arg = [];
        foreach ($arguments as $name => $value) {
            $arg[] = [$name, $value];
        }

        $this->io->table(
            ['name', 'value'],
            $arg
        );

        if (!$this->io->isDebug()) {
            $this->io->progressStart($worker->getCycles());
        }
    }


    protected function displayFinish()
    {
        if (!$this->io->isVerbose()) {
            return;
        }

        if (!$this->io->isDebug()) {
            $this->io->progressFinish();
        }

        $this->io->success("Finish");
    }


    /**
     * @param string $message
     */
    private function displayNote($message)
    {
        if (!$this->io->isVerbose()) {
            return;
        }

        if (!$this->io->isDebug()) {
            $this->io->progressFinish();
        }

        $this->io->note($message);
    }

    /**
     * @param string $message
     */
    private function displayError($message)
    {
        if ($this->io->isVerbose() && !$this->io->isDebug()) {
            $this->io->progressFinish();
        }

        $this->io->error($message);
    }

    /**
     * @param string $serviceName
     *
     * @return object|AsyncWorkerAbstract
     */
    protected function getWorker($serviceName)
    {
        return $this->getContainer()->get($serviceName);
    }
}