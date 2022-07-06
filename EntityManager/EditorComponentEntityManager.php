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

use Austral\ContentBlockBundle\Repository\EditorComponentRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;

use Austral\EntityBundle\EntityManager\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Austral EditorComponent EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class EditorComponentEntityManager extends EntityManager
{

  /**
   * @var EditorComponentRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return EditorComponentInterface
   */
  public function create(array $values = array()): EditorComponentInterface
  {
    return parent::create($values);
  }

  /**
   * @param string $orderByAttribute
   * @param string $orderByType
   *
   * @return ArrayCollection|array
   */
  public function selectAllEnabled(string $orderByAttribute = 'id', string $orderByType = "ASC")
  {
    return $this->repository->selectAllEnabled($orderByAttribute, $orderByType);
  }

}
