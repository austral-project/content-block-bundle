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
use Doctrine\ORM\Query\QueryException;

/**
 * Austral Guideline Repository.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineRepository extends EntityRepository
{

  /**
   * @param string $indexBy
   * @param string|null $domainId
   *
   * @return array
   * @throws QueryException
   */
  public function selectAllIndexBy(string $indexBy = "keyname", ?string $domainId = null): array
  {
    $queryBuilder = $this->createQueryBuilder('root');
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


}
