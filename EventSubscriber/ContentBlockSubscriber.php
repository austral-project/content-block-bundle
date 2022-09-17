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

use Austral\ContentBlockBundle\Entity\Component;
use Austral\ContentBlockBundle\Entity\ComponentValue;
use Austral\ContentBlockBundle\Entity\ComponentValues;
use Austral\ContentBlockBundle\Entity\EditorComponent;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentTypeInterface;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Event\ComponentEvent;
use Austral\ContentBlockBundle\Event\ContentBlockEvent;
use Austral\ContentBlockBundle\Event\GuidelineEvent;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Austral\ContentBlockBundle\Services\ContentBlockContainer;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Doctrine\Common\Collections\Collection;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function Symfony\Component\String\u;

/**
 * Austral ContentBlock Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ContentBlockSubscriber implements EventSubscriberInterface
{

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * @var ContentBlockContainer
   */
  protected ContentBlockContainer $contentBlockContainer;
  
  /**
   * @var UrlParameterManagement|null
   */
  protected ?UrlParameterManagement $urlParameterManagement;

  /**
   * ContentBlockSubscriber constructor.
   *
   * @param ContentBlockContainer $contentBlockContainer
   * @param EventDispatcherInterface $dispatcher
   * @param UrlParameterManagement|null $urlParameterManagement
   */
  public function __construct(ContentBlockContainer $contentBlockContainer, EventDispatcherInterface $dispatcher, ?UrlParameterManagement $urlParameterManagement = null)
  {
    $this->dispatcher = $dispatcher;
    $this->contentBlockContainer = $contentBlockContainer;
    $this->urlParameterManagement = $urlParameterManagement;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ContentBlockEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENTS_HYDRATE =>  ["componentsHydrate", 1024],
      GuidelineEvent::EVENT_AUSTRAL_CONTENT_BLOCK_GUIDELINE_INIT =>  ["guidelineInit", 1024],
      ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_INIT =>  ["componentInit", 1024],
    ];
  }

  /**
   * @param ContentBlockEvent $contentBlockEvent
   *
   * @throws \Exception
   */
  public function componentsHydrate(ContentBlockEvent $contentBlockEvent)
  {
    $this->contentBlockContainer->initComponentByObject($contentBlockEvent->getObject());
    $finalComponents = array();
    /** @var Component $componentObject */
    foreach($contentBlockEvent->getObject()->getComponents() as $containerName => $componentObjects)
    {
      $blockName = "default-0";
      $finalComponentsByContainer = array($blockName => array(
        "keyname"   =>  "default",
        "children"  => array()
      ));
      foreach($componentObjects as $componentObject)
      {
        if($componentObject->getComponentType() == "library")
        {
          /** @var LibraryInterface $library */
          $library = $componentObject->getLibrary();
          if($library->getAccessibleInContent() && $library->getIsEnabled())
          {
            $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = array(
              "id"                =>  $componentObject->getId(),
              "type"              =>  "library",
              "keyname"           =>  $componentObject->getLibrary()->getKeyname(),
            );
          }
        }
        else
        {
          $componentEvent = new ComponentEvent($contentBlockEvent->getObject(), $componentObject);
          $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_INIT);
          if(!$componentEvent->getIsDisabled())
          {
            if($componentObject->getEditorComponent()->getIsEnabled())
            {
              if($componentObject->getEditorComponent()->getIsContainer())
              {
                $keynameTemplate = $componentObject->getThemeKeyname() ?? $componentObject->getKeyname();
                $blockName = "{$keynameTemplate}-{$componentObject->getId()}";
                $finalComponentsByContainer[$blockName] = array(
                  "id"        =>  $componentObject->getId(),
                  "theme"     =>  $componentObject->getThemeKeyname(),
                  "option"    =>  $componentObject->getOptionKeyname(),
                  "type"      =>  "default",
                  "keyname"   =>  $componentObject->getKeyname(),
                  "children"  =>  array(),
                  "vars"      =>  $componentEvent->getVars()
                );
              }
              else
              {
                $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = array(
                  "id"                =>  $componentObject->getId(),
                  "keyname"           =>  $componentObject->getEditorComponent()->getKeyname(),
                  "type"              =>  "default",
                  "theme"             =>  $componentObject->getThemeKeyname(),
                  "option"            =>  $componentObject->getOptionKeyname(),
                  "layout"            =>  $componentObject->getLayoutKeyname(),
                  "templatePath"      =>  "{$contentBlockEvent->getRootTemplateDir()}\\{$componentObject->getEditorComponent()->getTemplatePathOrDefault()}",
                  "values"            =>  $this->componentValues($componentObject->getComponentValues()),
                  "vars"              =>  $componentEvent->getVars()
                );
              }
            }
          }
        }
      }
      $finalComponents[$containerName] = $finalComponentsByContainer;
    }
    $contentBlockEvent->getObject()->setComponentsTemplate($finalComponents);
  }

  /**
   * @param ComponentEvent $componentEvent
   */
  public function componentInit(ComponentEvent $componentEvent)
  {
  }

  /**
   * @param Collection $componentValues
   *
   * @return array
   */
  protected function componentValues(Collection $componentValues): array
  {
    $values = array();
    /** @var ComponentValue $componentValueObject */
    foreach($componentValues as $componentValueObject)
    {
      $editorComponent = $componentValueObject->getEditorComponentType();
      $values[$componentValueObject->getEditorComponentType()->getKeyname()] = array(
        "type"      =>  $componentValueObject->getEditorComponentType()->getType(),
        "classCss"  =>  $componentValueObject->getOptionsByKey("classCss", null)
      );
      if($editorComponent->getType() == "image" || $editorComponent->getType() == "file")
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()] = $componentValueObject;
      }
      else if ($editorComponent->getType() == "choice") {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['value'] = $componentValueObject->getOptionsByKey("choice");
      }
      else
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['value'] = $componentValueObject->getContent();
      }
      if($tag = $componentValueObject->getOptionsByKey("tags", null))
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['tag'] = $tag;
      }
      if($editorComponent->getType() == "textarea")
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['isWysiwyg'] = $editorComponent->getParameterByKey("isWysiwyg");
      }
      if($editorComponent->getType() == "movie")
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['isIframe'] = $editorComponent->getParameterByKey("isIframe");
      }
      
      if($linkType = $componentValueObject->getLinkType())
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"] = array(
          "anchor"  =>  $componentValueObject->getOptionsByKey("anchor", null),
          "target"  =>  $componentValueObject->getOptionsByKey("target", null),
          "url"     =>  $linkType == "internal" ? "" : $componentValueObject->getLinkUrl(),
          "type"    =>  $linkType,
        );
        if($linkType == "internal")
        {
          if($this->urlParameterManagement && $componentValueObject->getLinkEntityKey()) {
            $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]['url'] = "#INTERNAL_LINK_{$componentValueObject->getLinkEntityKey()}#";
            list($entity, $id) = explode(":", $componentValueObject->getLinkEntityKey());
            $urlParameter = $this->urlParameterManagement->getObjectRelationByClassnameAndId($entity,$id);
            $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]["urlParameter"] = $urlParameter;
          }
        }
        elseif($linkType == "external")
        {
          $linkUrl = $componentValueObject->getLinkUrl();
          if (!u($componentValueObject->getLinkUrl())->ignoreCase()->startsWith(array("https://", "http://"))) {
            $linkUrl = "//{$linkUrl}";
          }
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]['url'] = $linkUrl;
        }
        elseif($linkType == "file")
        {
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]['file'] = $componentValueObject;
        }
        elseif($linkType == "phone")
        {
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]['url'] = "tel:{$componentValueObject->getLinkPhone()}";
        }
        elseif($linkType == "email")
        {
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]["link"]['url'] = "mailto:{$componentValueObject->getLinkemail()}";
        }

      }
      if($editorComponent->getType() == "list" || $editorComponent->getType() == "group")
      {
        if($children = $componentValueObject->getChildren()->toArray())
        {
          $childrenValues = array();
          /** @var ComponentValues $child */
          foreach ($children as $child)
          {
            if($componentValueObject->getEditorComponentType()->getType() == "group")
            {
              $childrenValues = $this->componentValues($child->getChildren());
            }
            else
            {
              $childrenValues[$child->getPosition()] = $this->componentValues($child->getChildren());
            }
          }
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]["children"] = $childrenValues;
        }
      }
    }
    return $values;
  }


  /**
   * @param GuidelineEvent $guidelineEvent
   *
   * @throws \Exception
   */
  public function guidelineInit(GuidelineEvent $guidelineEvent)
  {
    $containerName = "default-0";
    $containersListe = array($containerName => array(
      "keyname"   =>  "default",
      "children"  => array()
    ));

    /** @var EditorComponentInterface $editorComponent */
    foreach($guidelineEvent->getEditorComponents() as $editorComponent)
    {
      if($editorComponent->getIsContainer())
      {
        if($editorComponent->getThemes())
        {
          foreach($editorComponent->getThemes() as $theme)
          {
            $keynameTemplate = $theme->getKeyname();
            $containersListe["{$keynameTemplate}-{$editorComponent->getId()}"] = $this->generateContainer($editorComponent, $theme);
          }
        }
        $keynameTemplate = $editorComponent->getKeyname();
        $containersListe["{$keynameTemplate}-{$editorComponent->getId()}"] = $this->generateContainer($editorComponent);
      }
    }

    $containersChoice = array();
    if($guidelineEvent->getContainerKey() != "all")
    {
      if(array_key_exists($guidelineEvent->getContainerKey(), $containersListe))
      {
        $containersChoice[$guidelineEvent->getContainerKey()] = $containersListe[$guidelineEvent->getContainerKey()];
      }
    }
    else
    {
      $containersChoice = $containersListe;
    }

    $finalComponents = array();
    foreach($containersChoice as $containerName => $container)
    {
      $components = array();
      /** @var EditorComponentInterface $editorComponent */
      foreach($guidelineEvent->getEditorComponents() as $editorComponent)
      {
        if(!$editorComponent->getIsContainer() && $editorComponent->getIsGuidelineView())
        {
          $combinations = array();
          /** @var EditorComponentTypeInterface $type */
          foreach($editorComponent->getEditorComponentTypes() as $type)
          {
            if($type->getType() == "title")
            {
              $combinations[$type->getKeyname()] = array();
              foreach($type->getParameterByKey("tags") as $tag)
              {
                $combinations[$type->getKeyname()][] = $tag;
              }
              if($type->getCanHasLink())
              {
                $combinations["link"] = array(
                  "noLink",
                  "internal",
                  "external",
                  "file"
                );
              }
            }
            elseif($type->getType() == "button")
            {
              $combinations[$type->getKeyname()] = array(
                "internal",
                "external",
                "file"
              );
            }
          }
          $allCombinations = array();
          if(count($combinations))
          {
            $combinationsKeys = array_keys($combinations);
            $allCombinations = $this->createAllCombinations($combinations, $combinationsKeys, 0);
          }

          foreach($editorComponent->getThemes() as $theme)
          {
            foreach($editorComponent->getOptions() as $option)
            {
              $this->generateComponent($components, $guidelineEvent, $editorComponent, $allCombinations, $theme, $option);
            }
            $this->generateComponent($components, $guidelineEvent, $editorComponent, $allCombinations, $theme, );
          }
          foreach($editorComponent->getOptions() as $option)
          {
            $this->generateComponent($components, $guidelineEvent, $editorComponent, $allCombinations, null, $option);
          }
          $this->generateComponent($components, $guidelineEvent, $editorComponent, $allCombinations);
        }
      }

      $finalComponents["master"][$containerName] = $container;
      $finalComponents["master"][$containerName]["children"] = $components;
    }
    $guidelineEvent->setFinalComponents($finalComponents);
    $guidelineEvent->setContainers($containersListe);
  }

  /**
   * @param EditorComponentInterface $editorComponent
   * @param null $theme
   *
   * @return array
   */
  protected function generateContainer(EditorComponentInterface $editorComponent, $theme = null): array
  {
    return array(
      "id"        =>  $editorComponent->getId(),
      "type"      =>  "default",
      "theme"     =>  $theme ? $theme->getKeyname() : "",
      "keyname"   =>  $editorComponent->getKeyname(),
      "children"  =>  array(),
      "vars"      =>  array()
    );
  }

  /**
   * @param $components
   * @param GuidelineEvent $guidelineEvent
   * @param EditorComponent $editorComponent
   * @param array $combinations
   * @param Theme|null $theme
   * @param Option|null $option
   * @param array $optionsValue
   *
   * @throws \Exception
   */
  protected function generateComponent(&$components, GuidelineEvent $guidelineEvent, EditorComponent $editorComponent, array $combinations = array(), ?Theme $theme = null, ?Option $option = null, array $optionsValue = array())
  {
    if($combinations)
    {
      foreach($combinations as $combinationKey => $combinationValue)
      {
        list($optionValueKey, $optionValue) = explode("@", $combinationKey);
        $optionsValue[$optionValueKey] = $optionValue;
        if(is_array($combinationValue))
        {
          $this->generateComponent($components, $guidelineEvent, $editorComponent, $combinationValue, $theme, $option, $optionsValue);
        }
        else
        {
          $this->generateComponent($components, $guidelineEvent, $editorComponent, array(), $theme, $option, $optionsValue);
        }
      }
    }
    else
    {
      /** @var Component $componentObject */
      $componentObject = clone $guidelineEvent->getComponentObject();
      $componentObject->setId(Uuid::uuid4()->toString());
      $componentObject->setEditorComponent($editorComponent);
      $componentObject->setOptionId($option ? $option->getId() : null);
      $componentObject->setThemeId($theme ? $theme->getId() : null);

      $componentEvent = new ComponentEvent($guidelineEvent->getDefaultObjectPage(), $componentObject);
      $componentEvent->setIsGuideline(true);
      $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_INIT);

      if(!$componentEvent->getIsDisabled())
      {
        $components[] = array(
          "id"                =>  $editorComponent->getId(),
          "keyname"           =>  $editorComponent->getKeyname(),
          "type"              =>  "default",
          "theme"             =>  $theme ? $theme->getKeyname() : "",
          "option"            =>  $option ? $option->getKeyname() : "",
          "templatePath"      =>  "Front\\{$editorComponent->getTemplatePathOrDefault()}",
          "values"            =>  $this->generateValues($editorComponent->getEditorComponentTypesWithChild(), $componentObject, $optionsValue),
          "vars"              =>  $componentEvent->getVars()
        );
      }
    }
  }

  /**
   * @param array $editorComponentTypes
   * @param Component $componentObject
   * @param null $optionsValue
   *
   * @return array
   */
  protected function generateValues(array $editorComponentTypes, Component $componentObject, $optionsValue = null): array
  {
    $lipsum = new LoremIpsum();
    $values = array();
    /** @var EditorComponentTypeInterface $type */
    foreach($editorComponentTypes as $type)
    {
      $values[$type->getKeyname()] = array(
        "type"      =>  $type->getType(),
        "classCss"  =>  $type->getCssClass(),
        "value"     =>  $lipsum->words(5),
      );

      $linkType = null;
      if($type->getType() == "title")
      {
        $values[$type->getKeyname()]['tag'] = $optionsValue[$type->getKeyname()];
        $values[$type->getKeyname()]['value'] = $optionsValue[$type->getKeyname()]." > ".$lipsum->words(3);
        if(array_key_exists("link", $optionsValue))
        {
          if($optionsValue["link"] !== "noLink")
          {
            $linkType = $optionsValue["link"];
          }
        }
      }
      elseif($type->getType() == "image" || $type->getType() == "file")
      {
        $componentValue = new \App\Entity\Austral\ContentBlockBundle\ComponentValue();
        $componentValue->setComponent($componentObject);
        $values[$type->getKeyname()] = $componentValue;
      }
      elseif($type->getType() == "textarea")
      {
        $values[$type->getKeyname()]['value'] = $lipsum->sentence();
        $values[$type->getKeyname()]['isWysiwyg'] = $type->getParameterByKey("isWysiwyg");
        if($type->getParameterByKey("isWysiwyg"))
        {
          $values[$type->getKeyname()]['value'] = "{$lipsum->paragraphs(2)} <ul>{$lipsum->words(3, '<li>$1</li>')}</ul>";
        }
      }
      elseif($type->getType() == "movie")
      {
        $values[$type->getKeyname()]['isIframe'] = $type->getParameterByKey("isIframe", false);
      }
      elseif($type->getType() == "button")
      {
        $values[$type->getKeyname()]['value'] = $lipsum->words(2);
        $linkType = $optionsValue[$type->getKeyname()];
      }
      elseif($type->getType() == "list" || $type->getType() == "group")
      {
        $values[$type->getKeyname()]['children'] = array();
      }
      if($linkType)
      {
        if($linkType == "internal")
        {
          $url = "/";
        }
        elseif($linkType == "external")
        {
          $url = "https://austral.dev";
        }
        else
        {
          $url = "https://austral.dev";
        }
        $values[$type->getKeyname()]['link'] = array(
          "url"       =>  $url,
          "anchor"    =>  "",
          "target"    =>  "",
          "type"      =>  $linkType
        );
      }

      if($type->getChildren())
      {
        if($type->getType() == "list")
        {
          for($i = 0; $i < 5; $i++)
          {
            $values[$type->getKeyname()]['children'][] = $this->generateValues($type->getChildren(), $componentObject, $optionsValue);
          }
        }
        else
        {
          $values[$type->getKeyname()]['children'] = $this->generateValues($type->getChildren(), $componentObject, $optionsValue);
        }
      }
    }
    return $values;
  }

  /**
   * @param $loopCreateTmp
   * @param $loopCreateTmpKeys
   * @param int $index
   * @param string $keyTmp
   *
   * @return array
   */
  protected function createAllCombinations($loopCreateTmp, $loopCreateTmpKeys, int $index, string &$keyTmp = ""): array
  {
    $keys = array();
    foreach($loopCreateTmp[$loopCreateTmpKeys[$index]] as $key => $value)
    {
      if($index+1 < count($loopCreateTmpKeys))
      {
        $keys["$loopCreateTmpKeys[$index]@$value"] = $this->createAllCombinations($loopCreateTmp, $loopCreateTmpKeys, $index+1, $key);
      }
      else
      {
        $keys["$loopCreateTmpKeys[$index]@$value"] = $value;
      }
    }
    return $keys;
  }

}