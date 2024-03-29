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

use Austral\ContentBlockBundle\Repository\LibraryRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Doctrine\ORM\Query\QueryException;

/**
 * Austral Library EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class LibraryEntityManager extends EntityManager
{

  /**
   * @var LibraryRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return LibraryInterface
   */
  public function create(array $values = array()): LibraryInterface
  {
    /** @var LibraryInterface|TranslateMasterInterface $object */
    $object = parent::create($values);
    $object->setCurrentLanguage($this->currentLanguage);
    $object->createNewTranslateByLanguage();
    return $object;
  }

  /**
   * @param string $indexBy
   * @param string|null $domainId
   *
   * @return array|int|mixed|string
   * @throws QueryException
   */
  public function selectAllIndexBy(string $indexBy = "keyname", ?string $domainId = null)
  {
    return $this->repository->selectAllIndexBy($indexBy, $domainId);
  }

  /**
   * @return array|int|mixed|string
   * @throws \Doctrine\ORM\Query\QueryException
   */
  public function selectAccessibleInContent( \Closure $closure = null)
  {
    return $this->repository->selectAccessibleInContent($closure);
  }

}
