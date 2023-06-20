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

use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Doctrine\Common\Collections\Collection;

/**
 * Austral ComponentValue Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ComponentValueInterface extends FileInterface
{

  /**
   * @return EditorComponentTypeInterface|null
   */
  public function getEditorComponentType(): ?EditorComponentTypeInterface;

  /**
   * @param EditorComponentTypeInterface|null $editorComponentType
   *
   * @return ComponentValueInterface
   */
  public function setEditorComponentType(?EditorComponentTypeInterface $editorComponentType): ComponentValueInterface;

  /**
   * @return ComponentInterface
   */
  public function getComponent(): ComponentInterface;

  /**
   * @param ComponentInterface $component
   *
   * @return ComponentValueInterface
   */
  public function setComponent(ComponentInterface $component): ComponentValueInterface;

  /**
   * @return ComponentValuesInterface|null
   */
  public function getParent(): ?ComponentValuesInterface;

  /**
   * @param ComponentValuesInterface|null $parent
   *
   * @return ComponentValueInterface
   */
  public function setParent(?ComponentValuesInterface $parent): ComponentValueInterface;

  /**
   * @return Collection
   */
  public function getChildren(): Collection;

  /**
   * @param Collection $children
   *
   * @return ComponentValueInterface
   */
  public function setChildren(Collection $children): ComponentValueInterface;

  /**
   * Add child
   *
   * @param ComponentValuesInterface $child
   *
   * @return $this
   */
  public function addChildren(ComponentValuesInterface $child): ComponentValueInterface;

  /**
   * @return int
   */
  public function getPosition(): int;

  /**
   * @param int $position
   *
   * @return ComponentValueInterface
   */
  public function setPosition(int $position): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getContent(): ?string;

  /**
   * @return \DateTime|null
   */
  public function getDate(): ?\DateTime;

  /**
   * @param \DateTime|null $date
   *
   * @return ComponentValueInterface
   */
  public function setDate(?\DateTime $date): ComponentValueInterface;

  /**
   * @param string|null $content
   *
   * @return ComponentValueInterface
   */
  public function setContent(?string $content): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getImage(): ?string;

  /**
   * @param string|null $image
   *
   * @return ComponentValueInterface
   */
  public function setImage(?string $image): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getFile(): ?string;

  /**
   * @param string|null $file
   *
   * @return ComponentValueInterface
   */
  public function setFile(?string $file): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getLinkType(): ?string;

  /**
   * @param string|null $linkType
   *
   * @return ComponentValueInterface
   */
  public function setLinkType(?string $linkType): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getLinkUrl(): ?string;

  /**
   * @param string|null $linkUrl
   *
   * @return ComponentValueInterface
   */
  public function setLinkUrl(?string $linkUrl): ComponentValueInterface;

  /**
   * @return string|null
   */
  public function getLinkEntityKey(): ?string;

  /**
   * @param string|null $linkEntityKey
   *
   * @return ComponentValueInterface
   */
  public function setLinkEntityKey(?string $linkEntityKey): ComponentValueInterface;

  /**
   * @return array
   */
  public function getOptions(): array;

  /**
   * @param string $key
   * @param null $default
   *
   * @return mixed|null
   */
  public function getOptionsByKey(string $key, $default = null);

  /**
   * @param array $options
   *
   * @return ComponentValueInterface
   */
  public function setOptions(array $options): ComponentValueInterface;

}

    
    
      