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

use Austral\ContentBlockBundle\Model\Guideline\GuidelineExtend;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral ContentBlock Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineEvent extends Event
{

  const EVENT_AUSTRAL_GUIDELINE_EXTEND = "austral.event.guideline.extend";

  /**
   * @var array
   */
  protected array $guidelinesExtendByCategory;

  /**
   * ContentBlock constructor.
   *
   */
  public function __construct(array $guidelinesExtendByCategory = array())
  {
    $this->guidelinesExtendByCategory = $guidelinesExtendByCategory;
  }

  /**
   * getGuidelinesExtendByCategory
   *
   * @return array
   */
  public function getGuidelinesExtendByCategory(): array
  {
    return $this->guidelinesExtendByCategory;
  }

  /**
   * @param GuidelineExtend $guidelineExtend
   * @return $this
   */
  public function addGuidelinesExtendByCategory(GuidelineExtend $guidelineExtend): GuidelineEvent
  {
    if(!$category = $guidelineExtend->getCategory()) {
      $category = "other";
    }
    if(!array_key_exists($category, $this->guidelinesExtendByCategory)) {
      $this->guidelinesExtendByCategory[$category] = array();
    }
    $this->guidelinesExtendByCategory[$category][] = $guidelineExtend;
    return $this;
  }

}