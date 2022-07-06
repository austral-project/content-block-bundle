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

/**
 * Austral LibraryTranslate Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface LibraryTranslateInterface
{


  /**
   * Constructor
   * @throws \Exception
   */
  public function __construct();

  /**
   * __ToString
   *
   * @return string
   */
  public function __toString();

  /**
   * @return string|null
   */
  public function getName(): ?string;

  /**
   * @param string|null $name
   *
   * @return $this
   */
  public function setName(?string $name): LibraryTranslateInterface;

  /**
   * @return bool
   */
  public function getIsEnabled(): bool;

  /**
   * @param bool $isEnabled
   *
   * @return $this
   */
  public function setIsEnabled(bool $isEnabled): LibraryTranslateInterface;

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
  public function setImage(?string $image): LibraryTranslateInterface;
}

    
    
      