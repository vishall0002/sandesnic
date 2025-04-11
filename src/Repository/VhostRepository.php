<?php

namespace App\Repository;

use App\Entity\Vhost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Vhost|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vhost|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vhost[]    findAll()
 * @method Vhost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VhostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vhost::class);
    }

    // /**
    //  * @return Vhost[] Returns an array of Vhost objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Vhost
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
