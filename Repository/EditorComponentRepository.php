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

use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\EntityBundle\Repository\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Austral EditorComponent Repository.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class EditorComponentRepository extends EntityRepository
{

  /**
   * @param string $key
   * @param string $value
   * @param \Closure|null $closure
   *
   * @return ?EditorComponentInterface
   * @throws NonUniqueResultException
   */
  public function retreiveByKey(string $key, string $value, \Closure $closure = null): ?EditorComponentInterface
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->where("root.{$key} = :{$key}")
      ->leftJoin("root.editorComponentTypes", "editorComponentTypes")->addSelect("editorComponentTypes")
      ->setParameter("{$key}", $value);

    $queryBuilder = $this->queryBuilderExtends("retreive-by-key", $queryBuilder);
    if($closure instanceof \Closure)
    {
      $closure->call($this, $queryBuilder);
    }
    $query = $queryBuilder->getQuery();
    try {
      $object = $query->getSingleResult();
    } catch (NoResultException $e) {
      $object = null;
    }
    return $object;
  }

  /**
   * @param string $orderByAttribute
   * @param string $orderByType
   *
   * @return ArrayCollection|array
   */
  public function selectAllEnabled(string $orderByAttribute = 'id', string $orderByType = "ASC")
  {
    $queryBuilder = $this->createQueryBuilder('root');
    if(strpos($orderByAttribute, ".") == false)
    {
      $orderByAttribute = "root.".$orderByAttribute;
    }
    $queryBuilder->where("root.isEnabled = :isEnabled")
      ->leftJoin("root.editorComponentTypes", "editorComponentTypes")->addSelect("editorComponentTypes")
      ->setParameter("isEnabled", true)
      ->indexBy("root", "root.keyname");
    $queryBuilder = $this->queryBuilderExtends("select-all-enabled", $queryBuilder);
    $queryBuilder->orderBy($orderByAttribute, $orderByType);
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }


}
