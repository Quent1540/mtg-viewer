<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 *
 * @method Card|null find($id, $lockMode = null, $lockVersion = null)
 * @method Card|null findOneBy(array $criteria, array $orderBy = null)
 * @method Card[]    findAll()
 * @method Card[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function getAllUuids(): array
    {
        $result =  $this->createQueryBuilder('c')
            ->select('c.uuid')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
        return array_column($result, 'uuid');
    }


    public function searchWithFilters(?string $name, ?string $setCode, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($name !== null && $name !== '') {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($setCode !== null && $setCode !== '') {
            $qb->andWhere('c.setCode = :setCode')
                ->setParameter('setCode', $setCode);
        }

        $countQb = clone $qb;
        $countQb->select('COUNT(c.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_OBJECT);

        return ['results' => $results, 'total' => $total];
    }

    public function getDistinctSetCodes(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('DISTINCT c.setCode as setCode')
            ->where('c.setCode IS NOT NULL')
            ->orderBy('c.setCode', 'ASC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_column($result, 'setCode');
    }

    public function searchByName(string $name, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->setMaxResults($limit)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
