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
use Exception;

/**
 * Austral Library Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface LibraryInterface
{

  /**
   * Constructor
   * @throws Exception
   */
  public function __construct();

  /**
   * @return int|string|null
   */
  public function __toString();

  /**
   * @return string
   */
  public function getId(): string;

  /**
   * @param string $id
   *
   * @return $this
   */
  public function setId(string $id): LibraryInterface;

  /**
   * @return Collection
   */
  public function getComponentsLibrary(): Collection;

  /**
   * @param Collection $componentsLibrary
   *
   * @return $this
   */
  public function setComponentsLibrary(Collection $componentsLibrary): LibraryInterface;

  /**
   * @return string|null
   * @throws Exception
   */
  public function getName(): ?string;

  /**
   * @param string|null $name
   *
   * @return $this
   * @throws Exception
   */
  public function setName(?string $name): LibraryInterface;

  /**
   * @return string|null
   */
  public function getKeyname(): ?string;

  /**
   * @param string|null $keyname
   *
   * @return $this
   */
  public function setKeyname(?string $keyname): LibraryInterface;

  /**
   * getGraphicItem
   *
   * @return string|null
   */
  public function getGraphicItem(): ?string;

  /**
   * setGraphicItem
   *
   * @param string|null $graphicItem
   * @return $this
   */
  public function setGraphicItem(?string $graphicItem): LibraryInterface;

  /**
   * @return bool
   */
  public function getIsNavigationMenu(): bool;

  /**
   * @param bool $isNavigationMenu
   *
   * @return $this
   */
  public function setIsNavigationMenu(bool $isNavigationMenu): LibraryInterface;

  /**
   * @return bool
   */
  public function getAccessibleInContent(): bool;

  /**
   * @param bool $accessibleInContent
   *
   * @return $this
   */
  public function setAccessibleInContent(bool $accessibleInContent): LibraryInterface;

  /**
   * @return bool
   * @throws Exception
   */
  public function getIsEnabled(): bool;

  /**
   * @param bool $isEnabled
   *
   * @return $this
   * @throws Exception
   */
  public function setIsEnabled(bool $isEnabled): LibraryInterface;

  /**
   * @return string|null
   */
  public function getTemplatePath(): ?string;

  /**
   * @param string|null $templatePath
   *
   * @return $this
   */
  public function setTemplatePath(?string $templatePath): LibraryInterface;

  /**
   * Get themes
   * @return array
   */
  public function getRestrictions(): array;

  /**
   * @param array $restrictions
   *
   * @return $this
   */
  public function setRestrictions(array $restrictions): LibraryInterface;

  /**
   * @return string|null
   */
  public function getCssClass(): ?string;

  /**
   * @param string|null $cssClass
   *
   * @return $this
   */
  public function setCssClass(?string $cssClass): LibraryInterface;

  /**
   * @return string
   * @throws Exception
   */
  public function getImage(): ?string;
  /**
   * @param $image
   *
   * @return $this
   * @throws Exception
   */
  public function setImage($image): LibraryInterface;

}

    
    
      