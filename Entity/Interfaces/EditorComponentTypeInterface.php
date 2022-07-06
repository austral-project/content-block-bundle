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

use Doctrine\Common\Collections\Collection;

/**
 * Austral EditorComponentTypeInterface Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface EditorComponentTypeInterface
{
  /**
   * @return EditorComponentInterface|null
   */
  public function getEditorComponent(): ?EditorComponentInterface;

  /**
   * @param EditorComponentInterface $editorComponent
   *
   * @return $this
   */
  public function setEditorComponent(EditorComponentInterface $editorComponent): EditorComponentTypeInterface;

  /**
   * @return Collection
   */
  public function getComponentValues(): Collection;

  /**
   * @param Collection $componentValues
   *
   * @return $this
   */
  public function setComponentValues(Collection $componentValues): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getParentId(): ?string;

  /**
   * @param string|null $parentId
   *
   * @return EditorComponentTypeInterface
   */
  public function setParentId(?string $parentId): EditorComponentTypeInterface;

  /**
   * @return EditorComponentTypeInterface|null
   */
  public function getParent(): ?EditorComponentTypeInterface;

  /**
   * @param EditorComponentTypeInterface $parent
   *
   * @return $this
   */
  public function setParent(EditorComponentTypeInterface $parent): EditorComponentTypeInterface;

  /**
   * @return array
   */
  public function getChildren(): array;

  /**
   * @param array $children
   *
   * @return $this
   */
  public function setChildren(array $children = array()): EditorComponentTypeInterface;

  /**
   * @return int
   */
  public function getPosition(): int;

  /**
   * @param int $position
   *
   * @return $this
   */
  public function setPosition(int $position): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getType(): ?string;

  /**
   * @param string|null $type
   *
   * @return $this
   */
  public function setType(?string $type): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getKeyname(): ?string;

  /**
   * @param string|null $keyname
   *
   * @return $this
   */
  public function setKeyname(?string $keyname): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getEntitled(): ?string;

  /**
   * @param string|null $entitled
   *
   * @return $this
   */
  public function setEntitled(?string $entitled): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getCssClass(): ?string;

  /**
   * @param string|null $cssClass
   *
   * @return $this
   */
  public function setCssClass(?string $cssClass): EditorComponentTypeInterface;

  /**
   * @return bool
   */
  public function getCanHasLink(): bool;

  /**
   * @param bool $canHasLink
   *
   * @return $this
   */
  public function setCanHasLink(bool $canHasLink): EditorComponentTypeInterface;

  /**
   * @return array
   */
  public function getParameters(): array;

  /**
   * @param array $parameters
   *
   * @return $this
   */
  public function setParameters(array $parameters): EditorComponentTypeInterface;
  /**
   * @param string $key
   * @param null $default
   *
   * @return array|mixed|string|null
   */
  public function getParameterByKey(string $key, $default = null);

  /**
   * @param string $key
   * @param null $value
   *
   * @return $this
   */
  public function setParameterByKey(string $key, $value = null): EditorComponentTypeInterface;

  /**
   * @return string|null
   */
  public function getBlockDirection(): ?string;

  /**
   * @param string|null $blockDirection
   *
   * @return $this
   */
  public function setBlockDirection(?string $blockDirection): EditorComponentTypeInterface;
}

    
    
      