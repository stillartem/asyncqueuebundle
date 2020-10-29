<?php

namespace Drivenow\AsyncWorkersBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract;
use Drivenow\AsyncWorkersBundle\Model\TaskStatus;

abstract class DoctrineTaskRepositoryAbstract extends EntityRepository
    implements TaskRepositoryInterface
{

    /**
     * @param TaskEntityAbstract $task
     * @param string $workerId
     *
     * @return bool
     */
    public function lock(TaskEntityAbstract $task, $workerId)
    {
        $qb = $this->_em->createQueryBuilder();
        $q = $qb->update($task->getClass(), 'w')
            ->set('w.workerId', $qb->expr()->literal($workerId))
            ->set('w.status', $qb->expr()->literal(TaskStatus::INPROGRESS))
            ->where('w.id = :taskid')
            ->andWhere("(w.workerId IS NULL OR w.workerId= :empty_value OR w.workerId='0')")
            ->setParameter('taskid', $task->getId())
            ->setParameter('empty_value', '')
            ->getQuery();

        $ret = (bool)$q->execute();
        if ($ret) {
            $task
                ->setStatus(TaskStatus::INPROGRESS)
                ->setWorkerId($workerId);
            $this->_em->refresh($task);
        }

        return $ret;
    }


    /**
     * @param TaskEntityAbstract $item
     *
     */
    public function save(TaskEntityAbstract $item)
    {
        $this->_em->persist($item);
        $this->_em->flush();
    }


    /**
     * @param TaskEntityAbstract $item
     */
    public function remove(TaskEntityAbstract $item)
    {
        $this->_em->remove($item);
        $this->_em->flush();
    }

    /**
     * @return QueryBuilder
     */
    public function removeAll()
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return void
     */
    public function clearMemory()
    {
        $this->_em->clear();
    }

    /**
     * @param int $limit
     * @param int $shardMax
     * @param int $shardNum
     *
     * @return TaskEntityAbstract[]
     */
    public function getTasks(int $limit = 100, int $shardMax = 1, int $shardNum = 1)
    {
        $this->ensure(
            $shardNum > 0 && $shardMax > 0 && $shardMax >= $shardNum,
            sprintf(
                "Shard number '%s' should be less or equals than Shard max '%s'",
                $shardNum,
                $shardMax
            )
        );

        $qb = $this->createQueryBuilder('w')
            ->where('(w.workerId IS NULL OR w.workerId= :empty_value)')
            ->andWhere("mod(w.id, :shard_max) = :shard_num")
            ->andWhere('(w.nextExecTime <= :today OR w.nextExecTime IS NULL)')
            ->andWhere('(w.status = :status_free OR w.status = :status_postponed)')
            ->orderBy('w.timestamp', 'ASC')
            ->setParameter('today', new \DateTime('now'), \Doctrine\DBAL\Types\Type::DATETIME)
            ->setParameter('status_free', TaskStatus::FREE)
            ->setParameter('status_postponed', TaskStatus::POSTPONED)
            ->setParameter('empty_value', '')
            ->setParameter('shard_max', $shardMax)
            ->setParameter('shard_num', $shardNum - 1);

        return $this->getCustomFilterForGetTasks($qb)
            ->getQuery()
            ->setMaxResults($limit)
            ->getResult();
    }


    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    protected function getCustomFilterForGetTasks(QueryBuilder $qb)
    {
        return $qb;
    }


    /**
     * @param TaskEntityAbstract $task
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    abstract public function markAsError(TaskEntityAbstract $task, \Throwable $exception);


    /**
     * @param TaskEntityAbstract $task
     *
     * @return void
     */
    abstract public function markAsDone(TaskEntityAbstract $task);


    /**
     * @param TaskEntityAbstract $task
     * @param PostponedException $exception
     *
     * @return void
     */
    abstract public function markAsPostponed(TaskEntityAbstract $task, PostponedException $exception);

    /**
     * @param bool $statement
     * @param string $message
     *
     * @throws \LogicException
     */
    protected function ensure($statement, $message)
    {
        if (!$statement) {
            throw new \LogicException($message);
        }
    }
}
