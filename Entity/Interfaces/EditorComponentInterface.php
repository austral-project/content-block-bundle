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

use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Doctrine\Common\Collections\Collection;

/**
 * Austral EditorComponent Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface EditorComponentInterface
{

  /**
   * @return Collection
   */
  public function getComponents(): Collection;

  /**
   * @param Collection $components
   *
   * @return EditorComponentInterface
   */
  public function setComponents(Collection $components): EditorComponentInterface;

  /**
   * @param string|null $type
   *
   * @return array
   */
  public function getEditorComponentTypesByTypes(?string $type = "all"): array;

  /**
   * @return array
   */
  public function getEditorComponentTypesWithChild(): array;

  /**
   * @return Collection
   */
  public function getEditorComponentTypes(): Collection;

  /**
   * @param Collection $editorComponentTypes
   *
   * @return EditorComponentInterface
   */
  public function setEditorComponentTypes(Collection $editorComponentTypes): EditorComponentInterface;

  /**
   * Add child
   *
   * @param EditorComponentTypeInterface $child
   *
   * @return $this
   */
  public function addEditorComponentType(EditorComponentTypeInterface $child): EditorComponentInterface;

  /**
   * Remove child
   *
   * @param EditorComponentTypeInterface $child
   *
   * @return $this
   */
  public function removeEditorComponentType(EditorComponentTypeInterface $child): EditorComponentInterface;

  /**
   * @return string|null
   */
  public function getName(): ?string;

  /**
   * @param string|null $name
   *
   * @return EditorComponentInterface
   */
  public function setName(?string $name): EditorComponentInterface;

  /**
   * @return string|null
   */
  public function getKeyname(): ?string;

  /**
   * @param string|null $keyname
   *
   * @return EditorComponentInterface
   */
  public function setKeyname(?string $keyname): EditorComponentInterface;

  /**
   * @return string|null
   */
  public function getTemplatePath(): ?string;

  /**
   * @param string|null $templatePath
   *
   * @return EditorComponentInterface
   */
  public function setTemplatePath(?string $templatePath): EditorComponentInterface;

  /**
   * Get themes
   * @return array
   */
  public function getThemes(): array;

  /**
   * @param string|null $themeId
   *
   * @return Theme|null
   */
  public function getThemeById(?string $themeId): ?Theme;

  /**
   * @param array $themes
   *
   * @return EditorComponentInterface
   */
  public function setThemes(array $themes): EditorComponentInterface;

  /**
   * @return array
   */
  public function getOptions(): array;

  /**
   * @param string|null $optionId
   *
   * @return Option|null
   */
  public function getOptionById(?string $optionId): ?Option;

  /**
   * @param array $options
   *
   * @return EditorComponentInterface
   */
  public function setOptions(array $options): EditorComponentInterface;

  /**
   * @return array
   */
  public function getLayouts(): array;

  /**
   * @param string|null $layoutId
   *
   * @return ?Layout
   */
  public function getLayoutById(?string $layoutId): ?Layout;

  /**
   * @param array $layouts
   *
   * @return EditorComponentInterface
   */
  public function setLayouts(array $layouts): EditorComponentInterface;

  /**
   * @return bool
   */
  public function getIsEnabled(): bool;

  /**
   * @param bool $isEnabled
   *
   * @return EditorComponentInterface
   */
  public function setIsEnabled(bool $isEnabled): EditorComponentInterface;

  /**
   * @return bool
   */
  public function getIsContainer(): bool;

  /**
   * @param bool $isContainer
   *
   * @return EditorComponentInterface
   */
  public function setIsContainer(bool $isContainer): EditorComponentInterface;

  /**
   * @return bool
   */
  public function getIsGuidelineView(): bool;

  /**
   * @param bool $isGuidelineView
   *
   * @return $this
   */
  public function setIsGuidelineView(bool $isGuidelineView): EditorComponentInterface;

  /**
   * Get themes
   * @return array
   */
  public function getRestrictions(): array;

  /**
   * @param array $restrictions
   *
   * @return EditorComponentInterface
   */
  public function setRestrictions(array $restrictions): EditorComponentInterface;

  /**
   * Get image
   * @return string|null
   */
  public function getImage(): ?string;

  /**
   * Set image
   *
   * @param string|null $image
   *
   * @return $this
   */
  public function setImage(?string $image): EditorComponentInterface;

  /**
   * @return string|null
   */
  public function getCategory(): ?string;

  /**
   * @param string|null $category
   *
   * @return $this
   */
  public function setCategory(?string $category): EditorComponentInterface;

  /**
   * @return int
   */
  public function getPosition(): int;
  /**
   * @param int $position
   *
   * @return $this
   */
  public function setPosition(int $position): EditorComponentInterface;

  /**
   * @return bool
   */
  public function getLayoutViewChoice(): bool;

  /**
   * @param bool $layoutViewChoice
   *
   * @return $this
   */
  public function setLayoutViewChoice(bool $layoutViewChoice): EditorComponentInterface;


}

    
    
      