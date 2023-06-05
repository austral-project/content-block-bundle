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

use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Theme;

/**
 * Class GuidelineComponent
 * @package Austral\ContentBlockBundle\Model\Guideline
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineComponent
{

  /**
   * @var array
   */
  protected array $layouts = array();

  /**
   * @var Layout|null
   */
  protected ?Layout $layout = null;

  /**
   * @var array
   */
  protected array $themes = array();

  /**
   * @var Theme|null
   */
  protected ?Theme $theme = null;

  /**
   * @var array
   */
  protected array $options = array();

  /**
   * @var Option|null
   */
  protected ?Option $option = null;
  
  /**
   * @var array
   */
  protected array $combinaisons = array();

  /**
   * @var array
   */
  protected array $combinaisonChoices = array();
  
  /**
   * @var array
   */
  protected array $component = array();

  public function __construct()
  {

  }

  /**
   * @return array
   */
  public function getLayouts(): array
  {
    return $this->layouts;
  }

  /**
   * @param array $layouts
   *
   * @return $this
   */
  public function setLayouts(array $layouts): GuidelineComponent
  {
    $this->layouts = $layouts;
    return $this;
  }

  /**
   * @return array
   */
  public function getThemes(): array
  {
    return $this->themes;
  }

  /**
   * @param array $themes
   *
   * @return $this
   */
  public function setThemes(array $themes): GuidelineComponent
  {
    $this->themes = $themes;
    return $this;
  }

  /**
   * @return array
   */
  public function getOptions(): array
  {
    return $this->options;
  }

  /**
   * @param array $options
   *
   * @return $this
   */
  public function setOptions(array $options): GuidelineComponent
  {
    $this->options = $options;
    return $this;
  }

  /**
   * @return array
   */
  public function getCombinaisons(): array
  {
    return $this->combinaisons;
  }

  /**
   * @param array $combinaisons
   *
   * @return $this
   */
  public function setCombinaisons(array $combinaisons): GuidelineComponent
  {
    $this->combinaisons = $combinaisons;
    return $this;
  }

  /**
   * @return array
   */
  public function getComponent(): array
  {
    return $this->component;
  }

  /**
   * @param array $component
   *
   * @return $this
   */
  public function setComponent(array $component): GuidelineComponent
  {
    $this->component = $component;
    return $this;
  }

  /**
   * @return Layout|null
   */
  public function getLayout(): ?Layout
  {
    return $this->layout;
  }

  /**
   * @param Layout|null $layout
   *
   * @return $this
   */
  public function setLayout(?Layout $layout): GuidelineComponent
  {
    $this->layout = $layout;
    return $this;
  }

  /**
   * @return Theme|null
   */
  public function getTheme(): ?Theme
  {
    return $this->theme;
  }

  /**
   * @param Theme|null $theme
   *
   * @return $this
   */
  public function setTheme(?Theme $theme): GuidelineComponent
  {
    $this->theme = $theme;
    return $this;
  }

  /**
   * @return Option|null
   */
  public function getOption(): ?Option
  {
    return $this->option;
  }

  /**
   * @param Option|null $option
   *
   * @return $this
   */
  public function setOption(?Option $option): GuidelineComponent
  {
    $this->option = $option;
    return $this;
  }

  /**
   * @return array
   */
  public function getCombinaisonChoices(): array
  {
    return $this->combinaisonChoices;
  }

  /**
   * @param array $combinaisonChoices
   *
   * @return $this
   */
  public function setCombinaisonChoices(array $combinaisonChoices): GuidelineComponent
  {
    $this->combinaisonChoices = $combinaisonChoices;
    return $this;
  }

}