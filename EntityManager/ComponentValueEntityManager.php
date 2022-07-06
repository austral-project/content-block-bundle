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

use Austral\ContentBlockBundle\Repository\ComponentValueRepository;
use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValueInterface;

use Austral\EntityBundle\EntityManager\EntityManager;

/**
 * Austral ComponentValue EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ComponentValueEntityManager extends EntityManager
{

  /**
   * @var ComponentValueRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return ComponentValueInterface
   */
  public function create(array $values = array()): ComponentValueInterface
  {
    return parent::create($values);
  }

}
