<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ContentBlockBundle\Entity\Traits;

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Austral EntityComponents Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityComponentsTrait
{

  /**
   * @var ?DateTime
   * @ORM\Column(name="components_updated", type="datetime", nullable=true)
   */
  protected ?DateTime $componentsUpdated;

  /**
   * @var array
   */
  protected array $components = array();

  /**
   * @var array
   */
  protected array $componentsTemplate = array();

  /**
   * @var array
   */
  protected array $componentsRemoved = array();

  /**
   * @return array
   */
  public function getComponents(): array
  {
    return $this->components;
  }

  /**
   * @return array
   */
  public function getComponentsContainerNames(): array
  {
    return array_keys($this->getComponents());
  }

  /**
   * @param string $containerName
   *
   * @return array
   */
  public function getComponentsByContainerName(string $containerName): array
  {
    return array_key_exists($containerName, $this->getComponents()) ? $this->getComponents()[$containerName] : array();
  }

  /**
   * @return array
   */
  public function getComponentsTemplate(): array
  {
    return $this->componentsTemplate;
  }

  /**
   * @param string $containerName
   *
   * @return array
   */
  public function getComponentsTemplateByContainerName(string $containerName = "master"): array
  {
    return array_key_exists($containerName, $this->getComponentsTemplate()) ? $this->getComponentsTemplate()[$containerName] : array();
  }

  /**
   * @return array
   */
  public function getComponentsRemoved(): array
  {
    return $this->componentsRemoved;
  }

  /**
   * @param array $components
   *
   * @return $this|ComponentsInterface
   */
  public function setComponents(array $components): ComponentsInterface
  {
    $this->components = $components;
    $this->setComponentsUpdated(new DateTime());
    return $this;
  }

  /**
   * @param array $componentsTemplate
   *
   * @return $this|ComponentsInterface
   */
  public function setComponentsTemplate(array $componentsTemplate): ComponentsInterface
  {
    $this->componentsTemplate = $componentsTemplate;
    return $this;
  }

  /**
   * Add child
   *
   * @param string $containerName
   * @param ComponentInterface $child
   *
   * @return $this|ComponentsInterface
   */
  public function addComponents(string $containerName, ComponentInterface $child): ComponentsInterface
  {
    if(!array_key_exists($containerName, $this->components))
    {
      $this->components[$containerName] = array();
    }
    if(!array_key_exists($child->getId(), $this->components[$containerName]))
    {
      $child->setObjectContainerName($containerName);
      $child->setObjectId($this->getId());
      $child->setObjectClassname($this->getClassname());
      $this->components[$containerName][$child->getId()] = $child;
    }
    $this->setComponentsUpdated(new DateTime());
    return $this;
  }

  /**
   * Remove child
   *
   * @param string $containerName
   * @param ComponentInterface $child
   *
   * @return $this|ComponentsInterface
   */
  public function removeComponents(string $containerName, ComponentInterface $child):ComponentsInterface
  {
    if(array_key_exists($containerName, $this->components) && array_key_exists($child->getId(), $this->components[$containerName]))
    {
      $child->setObjectId(null);
      $this->componentsRemoved[$child->getId()] = $child;
      unset($this->components[$containerName][$child->getId()]);
    }
    $this->setComponentsUpdated(new DateTime());
    return $this;
  }

  /**
   * @return ?DateTime
   */
  public function getComponentsUpdated(): ?DateTime
  {
    return $this->componentsUpdated;
  }

  /**
   * @param DateTime $componentsUpdated
   *
   * @return $this|ComponentsInterface
   */
  public function setComponentsUpdated(DateTime $componentsUpdated): ComponentsInterface
  {
    $this->componentsUpdated = $componentsUpdated;
    return $this;
  }

}