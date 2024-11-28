<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\ContentBlockBundle\EventSubscriber;

use Austral\ContentBlockBundle\Event\GuidelineEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral Guideline Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class GuidelineSubscriber implements EventSubscriberInterface
{

  /**
   * GuidelineSubscriber constructor.
   *
   */
  public function __construct()
  {
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array
  {
    return [
      GuidelineEvent::EVENT_AUSTRAL_GUIDELINE_EXTEND      =>  ["guidelineExtend", 1024],
    ];
  }

  /**
   * @param GuidelineEvent $guidelineEvent
   *
   * @throws \Exception
   */
  public function guidelineExtend(GuidelineEvent $guidelineEvent)
  {
  }

}