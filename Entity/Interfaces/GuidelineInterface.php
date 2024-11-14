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
 * Austral Guideline Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface GuidelineInterface
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
  public function setId(string $id): GuidelineInterface;

  /**
   * getEditorComponent
   *
   * @return EditorComponentInterface|null
   */
  public function getEditorComponent(): ?EditorComponentInterface;

  /**
   * @param EditorComponentInterface $editorComponent
   * @return $this
   */
  public function setEditorComponent(EditorComponentInterface $editorComponent): GuidelineInterface;

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
  public function setName(?string $name): GuidelineInterface;

  /**
   * @return string|null
   */
  public function getKeyname(): ?string;

  /**
   * @param string|null $keyname
   *
   * @return $this
   */
  public function setKeyname(?string $keyname): GuidelineInterface;

  /**
   * getCategory
   *
   * @return string|null
   */
  public function getCategory(): ?string;

  /**
   * @param string|null $category
   * @return $this
   */
  public function setCategory(?string $category): GuidelineInterface;


}

    
    
      