<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Model\Guideline;

use Ramsey\Uuid\Uuid;

/**
 * Class GuidelineExtend
 *
 * @package Austral\ContentBlockBundle\Model\Guideline
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineExtend
{
  /**
   * @var string
   */
  protected string $id;

  /**
   * @var string
   */
  protected string $name;

  /**
   * @var string|null
   */
  protected ?string $category = null;

  /**
   * @var string|null
   */
  protected ?string $templatePath = null;

  /**
   * create
   *
   * @param string $name
   * @param string|null $category
   * @param string|null $templatePath
   * @return self
   * @throws \Exception
   */
  public static function create(string $name, ?string $category = null, ?string $templatePath = null): GuidelineExtend
  {
    return new self($name, $category, $templatePath);
  }

  /**
   * GuidelineExtend constructor
   *
   * @param string $name
   * @param string|null $category
   * @param string|null $templatePath
   * @throws \Exception
   */
  public function __construct(string $name, ?string $category = null, ?string $templatePath = null)
  {
    $this->id = Uuid::uuid4()->toString();
    $this->name = $name;
    $this->category = $category;
    $this->templatePath = $templatePath;
  }

  /**
   * getId
   *
   * @return string
   */
  public function getId(): string
  {
    return $this->id;
  }

  /**
   * @param string $id
   * @return $this
   */
  public function setId(string $id): GuidelineExtend
  {
    $this->id = $id;
    return $this;
  }

  /**
   * getName
   *
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return $this
   */
  public function setName(string $name): GuidelineExtend
  {
    $this->name = $name;
    return $this;
  }

  /**
   * getCategory
   *
   * @return string|null
   */
  public function getCategory(): ?string
  {
    return $this->category;
  }

  /**
   * @param string|null $category
   * @return $this
   */
  public function setCategory(?string $category): GuidelineExtend
  {
    $this->category = $category;
    return $this;
  }

  /**
   * getTemplatePath
   *
   * @return string|null
   */
  public function getTemplatePath(): ?string
  {
    return $this->templatePath;
  }

  /**
   * @param string|null $templatePath
   * @return $this
   */
  public function setTemplatePath(?string $templatePath): GuidelineExtend
  {
    $this->templatePath = $templatePath;
    return $this;
  }

}