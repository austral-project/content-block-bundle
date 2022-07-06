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

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentValuesInterface;
use Austral\ContentBlockBundle\Repository\ComponentValuesRepository;

use Austral\EntityBundle\EntityManager\EntityManager;

/**
 * Austral ComponentValue EntityManager.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ComponentValuesEntityManager extends EntityManager
{

  /**
   * @var ComponentValuesRepository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return ComponentValuesInterface
   */
  public function create(array $values = array()): ComponentValuesInterface
  {
    return parent::create($values);
  }

}
