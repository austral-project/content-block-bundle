<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Listener;

use Austral\ContentBlockBundle\Event\ContentBlockEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchSelectObjectsEvent;
use Austral\ElasticSearchBundle\Model\Result;
use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

/**
 * Austral ElasticSearch Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ElasticSearchListener
{

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * @var Environment|null
   */
  protected ?Environment $twig = null;

  /**
   */
  public function __construct(EventDispatcherInterface $dispatcher)
  {
    $this->dispatcher = $dispatcher;
  }

  /**
   * setTwig
   *
   * @param Environment|null $twig
   * @return ElasticSearchListener
   */
  public function setTwig(?Environment $twig = null): ElasticSearchListener
  {
    $this->twig = $twig;
    return $this;
  }

  /**
   * @param ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent
   *
   * @return void
   * @throws QueryException
   * @throws \Exception
   */
  public function objects(ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent)
  {
    $objectsToHydrate = array();
    /** @var ObjectToHydrate $objectToHydrate */
    foreach ($elasticSearchSelectObjectsEvent->getObjectsToHydrate() as $objectToHydrate)
    {
      if($objectToHydrate->getObject() instanceof ComponentsInterface)
      {
        $this->blockComponentsDispatchEvent($objectToHydrate->getObject(), ContentBlockEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENTS_INIT);
        $this->blockComponentsDispatchEvent($objectToHydrate->getObject(), ContentBlockEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENTS_HYDRATE);

        $componentsTemplate = $objectToHydrate->getObject()->getComponentsTemplate();
        $componentValue = "";
        foreach($componentsTemplate as $componentTemplateByName)
        {
          foreach($componentTemplateByName as $componentTemplate)
          {
            if(array_key_exists("children", $componentTemplate) && count($componentTemplate["children"]) > 0)
            {
              foreach ($componentTemplate["children"] as $component)
              {
                if($component["type"] !== "library")
                {
                  try {
                    $componentValue .= $this->twig->render($component["templatePath"], array("component"=>$component, "template"=>array("name"=>"default")));
                    $componentValue .= "\n";
                  }
                  catch (\Exception $exception) {

                  }
                }
              }
            }
          }
        }
        $objectToHydrateClone = clone $objectToHydrate;
        $extraContent = $objectToHydrateClone->getValuesParametersByKey(Result::VALUE_EXTRA_CONTENT);
        $extraContent['components_html'] = $componentValue;
        $componentValueTxt = str_replace("<br>", "\n", $componentValue);
        $componentValueTxt = strip_tags($componentValueTxt);
        $componentValueTxt = preg_replace("/\s\s+/", " ", $componentValueTxt);
        $componentValueTxt = trim($componentValueTxt, "\n");
        $componentValueTxt = trim($componentValueTxt);
        $extraContent['components'] = $componentValueTxt;
        $objectToHydrateClone->addValuesParameters(Result::VALUE_EXTRA_CONTENT, $extraContent);
        $objectsToHydrate[$objectToHydrateClone->getElasticSearchId()] = $objectToHydrateClone;
      }
    }
    $elasticSearchSelectObjectsEvent->setObjectsToHydrate($objectsToHydrate);
  }

  protected function blockComponentsDispatchEvent(ComponentsInterface $object, string $eventName)
  {
    $contentBlockEvent = new ContentBlockEvent($object, "Front");
    $this->dispatcher->dispatch($contentBlockEvent, $eventName);
  }


}
