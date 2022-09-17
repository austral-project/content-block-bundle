<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Event;

use Austral\ContentBlockBundle\Entity\Interfaces\ComponentInterface;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Component Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ComponentEvent extends Event
{

  const EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_INIT = "austral.event.content_block.component_init";

  /**
   * @var ComponentsInterface
   */
  private ComponentsInterface $currentObject;

  /**
   * @var ComponentInterface
   */
  private ComponentInterface $component;

  /**
   * @var bool
   */
  private bool $disabled = false;

  /**
   * @var bool
   */
  private bool $isGuideline = false;

  /**
   * @var array
   */
  private array $vars = array();

  /**
   * FormEvent constructor.
   *
   * @param ComponentsInterface $currentObject
   * @param ComponentInterface $component
   */
  public function __construct(ComponentsInterface $currentObject, ComponentInterface $component)
  {
    $this->currentObject = $currentObject;
    $this->component = $component;
  }

  /**
   * @return ComponentsInterface
   */
  public function getCurrentObject(): ComponentsInterface
  {
    return $this->currentObject;
  }

  /**
   * @return ComponentInterface
   */
  public function getComponent(): ComponentInterface
  {
    return $this->component;
  }

  /**
   * @param ComponentInterface $component
   *
   * @return ComponentEvent
   */
  public function setComponent(ComponentInterface $component): ComponentEvent
  {
    $this->component = $component;
    return $this;
  }

  /**
   * @return array
   */
  public function getVars(): array
  {
    return $this->vars;
  }

  /**
   * @param string $varKey
   * @param mixed $varValue
   *
   * @return ComponentEvent
   */
  public function addVars(string $varKey, $varValue): ComponentEvent
  {
    $this->vars[$varKey] = $varValue;
    return $this;
  }

  /**
   * @param array $vars
   *
   * @return ComponentEvent
   */
  public function setVars(array $vars): ComponentEvent
  {
    $this->vars = $vars;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsDisabled(): bool
  {
    return $this->disabled;
  }

  /**
   * @param bool $disabled
   *
   * @return $this
   */
  public function setDisabled(bool $disabled): ComponentEvent
  {
    $this->disabled = $disabled;
    return $this;
  }

  /**
   * @return bool
   */
  public function getIsGuideline(): bool
  {
    return $this->isGuideline;
  }

  /**
   * @param bool $isGuideline
   *
   * @return $this
   */
  public function setIsGuideline(bool $isGuideline): ComponentEvent
  {
    $this->isGuideline = $isGuideline;
    return $this;
  }

}