<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Entity\Interfaces;

use Austral\ContentBlockBundle\Entity\ComponentValue;
use Austral\ContentBlockBundle\Entity\ComponentValues;
use Doctrine\Common\Collections\Collection;

/**
 * Austral ComponentValues Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ComponentValuesInterface
{

  /**
   * @return ComponentValue
   */
  public function getParent(): ComponentValue;

  /**
   * @param ComponentValue $parent
   *
   * @return $this
   */
  public function setParent(ComponentValue $parent): ComponentValues;

  /**
   * @return Collection
   */
  public function getChildren(): Collection;

  /**
   * @param Collection $children
   *
   * @return $this
   */
  public function setChildren(Collection $children): ComponentValues;

  /**
   * Add child
   *
   * @param ComponentValueInterface $child
   *
   * @return $this
   */
  public function addChildren(ComponentValueInterface $child): ComponentValues;

  /**
   * @return int
   */
  public function getPosition(): int;

  /**
   * @param int $position
   *
   * @return $this
   */
  public function setPosition(int $position): ComponentValues;

}

    
    
      