<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Repository;

use Austral\EntityBundle\Repository\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * Austral Library Repository.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class LibraryRepository extends EntityRepository
{

  /**
   * @var string $indexBy
   *
   * @return array|int|mixed|string
   * @throws \Doctrine\ORM\Query\QueryException
   */
  public function selectAllIndexBy(string $indexBy = "keyname", ?string $domainId = null)
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->leftJoin("root.translates", "translates")->addSelect("translates");
    $queryBuilder->indexBy("root", "root.{$indexBy}");
    if($domainId)
    {
      $queryBuilder->where("root.domainId = :domainId or root.domainId = :domainAll")
        ->setParameter("domainId", $domainId)
        ->setParameter("domainAll", "for-all-domains");
    }
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }

  /**
   * @return array|int|mixed|string
   */
  public function selectAccessibleInContent(\Closure $closure = null)
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->leftJoin("root.translates", "translates")->addSelect("translates");
    $queryBuilder->where("root.accessibleInContent = :accessibleInContent")
      ->setParameter("accessibleInContent", true);
    if($closure instanceof \Closure)
    {
      $closure->call($this, $queryBuilder);
    }
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }



}
