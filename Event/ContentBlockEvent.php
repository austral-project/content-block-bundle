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
class ContentBlockEvent extends Event
{

  const EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENTS_HYDRATE = "austral.event.content_block.components_hydrate";
  const EVENT_AUSTRAL_CONTENT_BLOCK_GUIDELINE_INIT = "austral.event.content_block.guideline_init";

  /**
   * @var EntityContentBlockInterface|null
   */
  private ?EntityContentBlockInterface $object;

  /**
   * @var array
   */
  private array $editorComponents = array();

  /**
   * @var ComponentInterface|null
   */
  private ?ComponentInterface $componentObject = null;

  /**
   * @var string
   */
  private string $rootTemplateDir;

  /**
   * FormEvent constructor.
   *
   * @param EntityContentBlockInterface|null $object
   * @param string $rootTemplateDir
   */
  public function __construct(?EntityContentBlockInterface $object = null, string $rootTemplateDir = "Front")
  {
    $this->object = $object;
    $this->rootTemplateDir = $rootTemplateDir;
  }

  /**
   * @return EntityContentBlockInterface|null
   */
  public function getObject(): ?EntityContentBlockInterface
  {
    return $this->object;
  }

  /**
   * @param EntityContentBlockInterface $object
   *
   * @return ContentBlockEvent
   */
  public function setObject(EntityContentBlockInterface $object): ContentBlockEvent
  {
    $this->object = $object;
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
   * @return ContentBlockEvent
   */
  public function setRootTemplateDir(string $rootTemplateDir): ContentBlockEvent
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
  public function setEditorComponents(array $editorComponents): ContentBlockEvent
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
  public function setComponentObject(?ComponentInterface $componentObject): ContentBlockEvent
  {
    $this->componentObject = $componentObject;
    return $this;
  }

}