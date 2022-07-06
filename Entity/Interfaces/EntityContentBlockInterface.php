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

use DateTime;

/**
 * Austral EntityContentBlock Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface EntityContentBlockInterface
{

  /**
   * @return array
   */
  public function getComponents(): array;

  /**
   * @param string $containerName
   *
   * @return array
   */
  public function getComponentsByContainerName(string $containerName): array;

  /**
   * @return array
   */
  public function getComponentsRemoved(): array;

  /**
   * @return array
   */
  public function getComponentsContainerNames(): array;

  /**
   * @return array
   */
  public function getComponentsTemplate(): array;

  /**
   * @param string $containerName
   *
   * @return array
   */
  public function getComponentsTemplateByContainerName(string $containerName = "master"): array;

  /**
   * @param array $components
   *
   * @return $this
   */
  public function setComponents(array $components): EntityContentBlockInterface;

  /**
   * @param array $componentsTemplate
   *
   * @return $this|EntityContentBlockInterface
   */
  public function setComponentsTemplate(array $componentsTemplate): EntityContentBlockInterface;

  /**
   * Add child
   *
   * @param string $containerName
   * @param ComponentInterface $child
   *
   * @return $this
   */
  public function addComponents(string $containerName, ComponentInterface $child): EntityContentBlockInterface;

  /**
   * Remove child
   *
   * @param string $containerName
   * @param ComponentInterface $child
   *
   * @return $this
   */
  public function removeComponents(string $containerName, ComponentInterface $child): EntityContentBlockInterface;

  /**
   * @return ?DateTime
   */
  public function getComponentsUpdated(): ?DateTime;

  /**
   * @param DateTime $componentsUpdated
   *
   * @return EntityContentBlockInterface
   */
  public function setComponentsUpdated(DateTime $componentsUpdated): EntityContentBlockInterface;



}
