<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\EntityManager;

use Austral\ContentBlockBundle\Repository\GuidelineRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\GuidelineInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Doctrine\ORM\Query\QueryException;

/**
 * Austral Guideline EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class GuidelineEntityManager extends EntityManager
{

  /**
   * @var GuidelineRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return GuidelineInterface
   */
  public function create(array $values = array()): GuidelineInterface
  {
    /** @var GuidelineInterface|TranslateMasterInterface $object */
    $object = parent::create($values);
    return $object;
  }

  /**
   * @param string $indexBy
   * @param string|null $domainId
   *
   * @return array
   * @throws QueryException
   */
  public function selectAllIndexBy(string $indexBy = "keyname", ?string $domainId = null): array
  {
    return $this->repository->selectAllIndexBy($indexBy, $domainId);
  }

}
