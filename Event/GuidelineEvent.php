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
use Austral\ContentBlockBundle\Entity\Interfaces\EntityContentBlockInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral ContentBlock Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineEvent extends Event
{

  const EVENT_AUSTRAL_CONTENT_BLOCK_GUIDELINE_INIT = "austral.event.content_block.guideline_init";

  /**
   * @var array
   */
  private array $editorComponents = array();

  /**
   * @var ComponentInterface|null
   */
  private ?ComponentInterface $componentObject = null;

  /**
   * @var EntityContentBlockInterface|null
   */
  private ?EntityContentBlockInterface $defaultObjectPage = null;

  /**
   * @var string
   */
  private string $containerKey;

  /**
   * @var array
   */
  private array $containers = array();

  /**
   * @var array
   */
  private array $finalComponents = array();

  /**
   * @var string
   */
  private string $rootTemplateDir;

  /**
   * FormEvent constructor.
   *
   * @param string $containerKey
   * @param string $rootTemplateDir
   */
  public function __construct(string $containerKey = "all", string $rootTemplateDir = "Front")
  {
    $this->containerKey = $containerKey;
    $this->rootTemplateDir = $rootTemplateDir;
  }

  /**
   * @return string
   */
  public function getContainerKey(): string
  {
    return $this->containerKey;
  }

  /**
   * @param string $containerKey
   *
   * @return $this
   */
  public function setContainerKey(string $containerKey): GuidelineEvent
  {
    $this->containerKey = $containerKey;
    return $this;
  }

  /**
   * @return string
   */
  public function getRootTemplateDir(): string
  {
    return $this->rootTemplateDir;
  }

  /**
   * @param string $rootTemplateDir
   *
   * @return GuidelineEvent
   */
  public function setRootTemplateDir(string $rootTemplateDir): GuidelineEvent
  {
    $this->rootTemplateDir = $rootTemplateDir;
    return $this;
  }

  /**
   * @return array
   */
  public function getEditorComponents(): array
  {
    return $this->editorComponents;
  }

  /**
   * @param array $editorComponents
   *
   * @return $this
   */
  public function setEditorComponents(array $editorComponents): GuidelineEvent
  {
    $this->editorComponents = $editorComponents;
    return $this;
  }

  /**
   * @return ComponentInterface|null
   */
  public function getComponentObject(): ?ComponentInterface
  {
    return $this->componentObject;
  }

  /**
   * @param ComponentInterface|null $componentObject
   *
   * @return $this
   */
  public function setComponentObject(?ComponentInterface $componentObject): GuidelineEvent
  {
    $this->componentObject = $componentObject;
    return $this;
  }

  /**
   * @return EntityContentBlockInterface|null
   */
  public function getDefaultObjectPage(): ?EntityContentBlockInterface
  {
    return $this->defaultObjectPage;
  }

  /**
   * @param EntityContentBlockInterface|null $defaultObjectPage
   *
   * @return $this
   */
  public function setDefaultObjectPage(?EntityContentBlockInterface $defaultObjectPage): GuidelineEvent
  {
    $this->defaultObjectPage = $defaultObjectPage;
    return $this;
  }

  /**
   * @return array
   */
  public function getFinalComponents(): array
  {
    return $this->finalComponents;
  }

  /**
   * @param array $finalComponents
   *
   * @return $this
   */
  public function setFinalComponents(array $finalComponents): GuidelineEvent
  {
    $this->finalComponents = $finalComponents;
    return $this;
  }

  /**
   * @return array
   */
  public function getContainers(): array
  {
    return $this->containers;
  }

  /**
   * @param array $containers
   *
   * @return $this
   */
  public function setContainers(array $containers): GuidelineEvent
  {
    $this->containers = $containers;
    return $this;
  }

}