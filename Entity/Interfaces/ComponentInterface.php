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
 * Austral Component Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ComponentInterface
{

  public function getType();

  /**
   * @return string
   */
  public function getComponentType(): string;

  /**
   * @param string $id
   *
   * @return ComponentInterface
   */
  public function setId(string $id): ComponentInterface;

  /**
   * @return string
   */
  public function getObjectClassname(): string;

  /**
   * @param string $objectClassname
   *
   * @return ComponentInterface
   */
  public function setObjectClassname(string $objectClassname): ComponentInterface;

  /**
   * @return string|null
   */
  public function getObjectId(): ?string;

  /**
   * @param string|null $objectId
   *
   * @return ComponentInterface
   */
  public function setObjectId(?string $objectId): ComponentInterface;

  /**
   * @return string|null
   */
  public function getObjectContainerName(): ?string;

  /**
   * @param string|null $objectContainerName
   *
   * @return ComponentInterface
   */
  public function setObjectContainerName(?string $objectContainerName): ComponentInterface;

  /**
   * @return EditorComponentInterface|null
   */
  public function getEditorComponent(): ?EditorComponentInterface;

  /**
   * @param EditorComponentInterface $editorComponent
   *
   * @return ComponentInterface
   */
  public function setEditorComponent(EditorComponentInterface $editorComponent): ComponentInterface;

  /**
   * @return LibraryInterface|null
   */
  public function getLibrary(): ?LibraryInterface;

  /**
   * @param LibraryInterface $library
   *
   * @return ComponentInterface
   */
  public function setLibrary(LibraryInterface $library): ComponentInterface;

  /**
   * @return Collection
   */
  public function getComponentValues(): Collection;

  /**
   * getComponentValuesByEditorComponentType
   * @param EditorComponentTypeInterface $editorComponentType
   * @return ComponentValueInterface
   */
  public function getComponentValuesByEditorComponentType(EditorComponentTypeInterface $editorComponentType): ComponentValueInterface;

  /**
   * @param Collection $componentValues
   *
   * @return ComponentInterface
   */
  public function setComponentValues(Collection $componentValues): ComponentInterface;

  /**
   * Add child
   *
   * @param ComponentValueInterface $child
   *
   * @return ComponentInterface
   */
  public function addComponentValues(ComponentValueInterface $child): ComponentInterface;

  /**
   * Remove child
   *
   * @param ComponentValueInterface $child
   *
   * @return ComponentInterface
   */
  public function removeComponentValues(ComponentValueInterface $child): ComponentInterface;

  /**
   * @return int
   */
  public function getPosition(): int;

  /**
   * @param int $position
   *
   * @return ComponentInterface
   */
  public function setPosition(int $position): ComponentInterface;

  /**
   * @return string|null
   */
  public function getThemeId(): ?string;

  /**
   * @param string|null $themeId
   *
   * @return ComponentInterface
   */
  public function setThemeId(?string $themeId): ComponentInterface;

  /**
   * @return string|null
   */
  public function getOptionId(): ?string;

  /**
   * @param string|null $optionId
   *
   * @return ComponentInterface
   */
  public function setOptionId(?string $optionId): ComponentInterface;

  /**
   * @return string|null
   */
  public function getLayoutId(): ?string;

  /**
   * @param string|null $layoutId
   *
   * @return ComponentInterface
   */
  public function setLayoutId(?string $layoutId): ComponentInterface;

}

    
    
      