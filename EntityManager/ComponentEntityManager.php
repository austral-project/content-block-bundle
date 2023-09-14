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

use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Repository\ComponentRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\QueryException;

/**
 * Austral Component EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ComponentEntityManager extends EntityManager
{

  /**
   * @var ComponentRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return ComponentInterface
   */
  public function create(array $values = array()): ComponentInterface
  {
    return parent::create();
  }

  /**
   * @param string $classname
   *
   * @return array
   * @throws QueryException
   */
  public function selectArrayComponentsContainerNameByClassname(string $classname): array
  {
    return $this->repository->selectArrayComponentsContainerNameByClassname($classname);
  }

  /**
   * @param $objectId
   * @param string $classname
   * @param bool $full
   *
   * @return Collection|array
   * @throws QueryException
   */
  public function selectComponentsByObjectIdAndClassname($objectId, string $classname, bool $full = false)
  {
    if($full && $this->repository->isPgsql())
    {
      return $this->repository->selectComponentsByObjectIdAndClassnameFull($objectId, $classname);
    }
    return $this->repository->selectComponentsByObjectIdAndClassname($objectId, $classname);
  }

  /**
   * @param EditorComponentInterface $editorComponent
   *
   * @return array
   * @throws QueryException
   */
  public function selectArrayComponentsByEditorComponent(EditorComponentInterface $editorComponent): array
  {
    return $this->repository->selectArrayComponentsByEditorComponent($editorComponent);
  }

  /**
   * @param LibraryInterface $library
   *
   * @return array
   * @throws QueryException
   */
  public function selectArrayComponentsByLibrary(LibraryInterface $library): array
  {
    return $this->repository->selectArrayComponentsByLibrary($library);
  }

}
