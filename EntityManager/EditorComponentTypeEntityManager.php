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

use Austral\ContentBlockBundle\Repository\EditorComponentTypeRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;

use Austral\EntityBundle\EntityManager\EntityManager;

/**
 * Austral EditorComponentType EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class EditorComponentTypeEntityManager extends EntityManager
{

  /**
   * @var EditorComponentTypeRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return EditorComponentTypeInterface
   */
  public function create(array $values = array()): EditorComponentTypeInterface
  {
    return parent::create($values);
  }

}
