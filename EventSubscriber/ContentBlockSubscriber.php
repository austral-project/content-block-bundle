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
use Austral\ContentBlockBundle\Mapping\ObjectContentBlockMapping;
use Austral\ContentBlockBundle\Model\Editor\Layout;
use Austral\ContentBlockBundle\Model\Editor\Option;
use Austral\ContentBlockBundle\Model\Editor\Theme;
use Austral\ContentBlockBundle\Model\Guideline\GuidelineComponent;
use Austral\ContentBlockBundle\Services\ContentBlockContainer;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\EntityTranslateBundle\Mapping\EntityTranslateMapping;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\ToolsBundle\AustralTools;
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
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var EntityManager
   */
  protected EntityManager $entityManager;

  /**
   * @var UrlParameterManagement|null
   */
  protected ?UrlParameterManagement $urlParameterManagement;

  /**
   * ContentBlockSubscriber constructor.
   *
   * @param ContentBlockContainer $contentBlockContainer
   * @param EventDispatcherInterface $dispatcher
   * @param Mapping $mapping
   * @param EntityManager $entityManager
   * @param UrlParameterManagement|null $urlParameterManagement
   */
  public function __construct(ContentBlockContainer $contentBlockContainer,
    EventDispatcherInterface $dispatcher,
    Mapping $mapping,
    EntityManager $entityManager,
    ?UrlParameterManagement $urlParameterManagement = null
  )
  {
    $this->mapping = $mapping;
    $this->entityManager = $entityManager;
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
      ContentBlockEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENTS_INIT    =>  ["componentsInit", 1024],
      GuidelineEvent::EVENT_AUSTRAL_CONTENT_BLOCK_GUIDELINE_INIT        =>  ["guidelineInit", 1024],
      ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_HYDRATE        =>  ["componentInit", 1024],
    ];
  }

  public function componentsInit(ContentBlockEvent $contentBlockEvent)
  {
    $this->contentBlockContainer->initComponentByObject($contentBlockEvent->getObject(), false);
    /** @var Component $componentObject */
    foreach($contentBlockEvent->getObject()->getComponents() as $componentObjects)
    {
      foreach($componentObjects as $componentObject)
      {
        $componentEvent = new ComponentEvent($contentBlockEvent->getObject(), $componentObject);
        $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_INIT);
      }
    }
  }

  /**
   * @param ContentBlockEvent $contentBlockEvent
   *
   * @throws \Exception
   */
  public function componentsHydrate(ContentBlockEvent $contentBlockEvent)
  {
    $finalComponents = array();
    $finalComponentsByTypes = array();

    /** @var Component $componentObject */
    foreach($contentBlockEvent->getObject()->getComponents() as $containerName => $componentObjects)
    {
      $blockName = "default-0";
      $finalComponentsByContainer = array($blockName => array(
        "keyname"   =>  "default",
        "children"  => array()
      ));
      $finalComponentsByContainerByTypes = array($blockName => array(
        "keyname"   =>  "default",
        "children"  => array()
      ));
      foreach($componentObjects as $componentObject)
      {
        if($componentObject->getComponentType() === "library")
        {
          /** @var LibraryInterface $library */
          $library = $componentObject->getLibrary();
          if($library->getAccessibleInContent() && $library->getIsEnabled())
          {
            $componentValues = array(
              "id"                =>  $componentObject->getId(),
              "type"              =>  "library",
              "keyname"           =>  $componentObject->getLibrary()->getKeyname(),
            );
            $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
            $finalComponentsByContainerByTypes[$blockName]['children'][$componentObject->getLibrary()->getKeyname()][] = $componentValues;
          }
        }
        else
        {
          $componentEvent = new ComponentEvent($contentBlockEvent->getObject(), $componentObject);
          $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_HYDRATE);
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
                $componentValues = array(
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
                $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
                $finalComponentsByContainerByTypes[$blockName]['children'][$componentObject->getEditorComponent()->getKeyname()][] = $componentValues;
              }
            }
          }
        }
      }
      $finalComponents[$containerName] = $finalComponentsByContainer;
      $finalComponentsByTypes[$containerName] = $finalComponentsByContainerByTypes;
    }
    $contentBlockEvent->getObject()
      ->setComponentsTemplate($finalComponents)
      ->setComponentsTemplateByTypes($finalComponentsByTypes);
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
        "id"        =>  $componentValueObject->getId(),
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
      if($editorComponent->getType() == "object")
      {
        if($objectId = $componentValueObject->getOptionsByKey("objectId"))
        {
          $entityClass = $editorComponent->getParameterByKey("entityClass");
          if($entityClass === "all")
          {
            list($entityClass, $objectId) = explode("::", $objectId);
          }
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]['objectId'] = "{$entityClass}::{$objectId}";
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]['object'] = $this->getObjectsByEntityClassAndId($entityClass, $objectId);
        }
      }
      if($editorComponent->getType() == "movie")
      {
        $values[$componentValueObject->getEditorComponentType()->getKeyname()]['isIframe'] = $editorComponent->getParameterByKey("isIframe");
        if($videoUrl = $componentValueObject->getContent())
        {
          if(strpos($videoUrl, "youtube") || str_contains($videoUrl, "youtu."))
          {
            if(str_contains($videoUrl, "youtu."))
            {
              preg_match('/youtu.[\w]{0,}\/([\w|-]{0,})/', $videoUrl, $matches);
            }
            else
            {
              preg_match('/v=([\w|-]{0,})/', $videoUrl, $matches);
            }
            $videoId = AustralTools::getValueByKey($matches, 1, null);
            $videoInfos = array(
              "type"              =>  "youtube",
              "key"               =>  $videoId,
              "url"               =>  "https://www.youtube.com/embed/{$videoId}",
              "title"             =>  "Video Youtube {$videoId}",
              "thumbnail"         =>  array(
                "path"              =>  "https://img.youtube.com/vi/{$videoId}/",
                "default"           =>  "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
              )

            );
          }
          elseif(str_contains($videoUrl, "https://vimeo.com"))
          {
            preg_match('/vimeo.com\/([\d]{0,})/', $videoUrl, $matches);
            $videoId = AustralTools::getValueByKey($matches, 1, null);

            $vimeoInfos = unserialize(file_get_contents("http://vimeo.com/api/v2/video/{$videoId}.php"));
            $vimeoInfos = AustralTools::first($vimeoInfos);
            if($thumbnailPath = AustralTools::getValueByKey($vimeoInfos, "thumbnail_small", null))
            {
              $thumbnailPath = preg_replace("/-d_(.*)/", "-d_", $thumbnailPath);
            }
            $videoInfos = array(
              "type"              =>  "vimeo",
              "key"               =>  $videoId,
              "url"               =>  "https://player.vimeo.com/video/{$videoId}",
              "title"             =>  AustralTools::getValueByKey($vimeoInfos, "title"),
              "thumbnail"         =>  array(
                "path"              =>  $thumbnailPath,
                "default"           =>  "{$thumbnailPath}1980",
              )
            );
          }
          else
          {
            $videoInfos = array(
              "type"              =>  "default",
              "url"               =>  $videoUrl
            );
          }
          $values[$componentValueObject->getEditorComponentType()->getKeyname()]['video'] = $videoInfos;
        }
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
            $separator = ":";
            if(strpos($componentValueObject->getLinkEntityKey(), "::") !== false)
            {
              $separator = "::";
            }
            list($entity, $id) = explode($separator, $componentValueObject->getLinkEntityKey());
            $urlParameter = $this->urlParameterManagement->getUrlParameterByObjectClassnameAndId($entity,$id);
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
        $hasThemeDefault = false;
        if($editorComponent->getThemes())
        {
          foreach($editorComponent->getThemes() as $theme)
          {
            $keynameTemplate = $theme->getKeyname();
            if($keynameTemplate === "default")
            {
              $hasThemeDefault = true;
            }
            $containersListe["{$keynameTemplate}-{$editorComponent->getId()}"] = $this->generateContainer($editorComponent, $theme);
          }
        }
        if(!$hasThemeDefault)
        {
          $keynameTemplate = $editorComponent->getKeyname();
          $containersListe["{$keynameTemplate}-{$editorComponent->getId()}"] = $this->generateContainer($editorComponent);
        }
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
    $guidelineFormValues = $guidelineEvent->getGuidelineFormValues();
    foreach($containersChoice as $containerName => $container)
    {
      $guidelineComponents = array();
      /** @var EditorComponentInterface $editorComponent */
      foreach($guidelineEvent->getEditorComponents() as $editorComponent)
      {
        if(!$editorComponent->getIsContainer() && $editorComponent->getIsGuidelineView())
        {
          $guidelineComponent = new GuidelineComponent();
          $guidelineComponent->setCombinaisons($this->combinaisons($editorComponent));
          $guidelineComponent->setCombinaisonChoices($this->combinaisonDefaultValues($editorComponent));
          $guidelineComponent->setLayouts($editorComponent->getLayouts());
          $guidelineComponent->setThemes($editorComponent->getThemes());
          $guidelineComponent->setOptions($editorComponent->getOptions());

          if($editorComponent->getLayouts())
          {
            /** @var Layout $firstLayout */
            $firstLayout = AustralTools::first($editorComponent->getLayouts());
            $guidelineComponent->setLayout($firstLayout);
          }

          if(AustralTools::getValueByKey($guidelineFormValues, "id") === $editorComponent->getId())
          {
            $guidelineComponent->setLayout(AustralTools::getValueByKey($editorComponent->getLayouts(), AustralTools::getValueByKey($guidelineFormValues, "layout")));
            $guidelineComponent->setTheme(AustralTools::getValueByKey($editorComponent->getThemes(), AustralTools::getValueByKey($guidelineFormValues, "theme")));
            $guidelineComponent->setOption(AustralTools::getValueByKey($editorComponent->getOptions(), AustralTools::getValueByKey($guidelineFormValues, "option")));
            $guidelineComponent->setCombinaisonChoices(AustralTools::getValueByKey($guidelineFormValues, "combinaisons", array()));
          }

          if($component = $this->generateComponent($guidelineEvent, $editorComponent, $guidelineComponent->getLayout(), $guidelineComponent->getTheme(), $guidelineComponent->getOption(), $guidelineComponent->getCombinaisonChoices()))
          {
            $guidelineComponent->setComponent($component);
            $guidelineComponents[] = $guidelineComponent;
          }
        }
      }
      $finalComponents["master"][$containerName] = $container;
      $finalComponents["master"][$containerName]["children"] = $guidelineComponents;
    }
    $guidelineEvent->setFinalComponents($finalComponents);
    $guidelineEvent->setContainers($containersListe);
  }

  /**
   * combinaisons
   *
   * @param EditorComponentInterface $editorComponent
   *
   * @return array
   */
  protected function combinaisons(EditorComponentInterface $editorComponent): array
  {
    $combinaisons = array();
    /** @var EditorComponentTypeInterface $type */
    foreach ($editorComponent->getEditorComponentTypes() as $type)
    {
      if($type->getType() === "title")
      {
        $combinaisons[$type->getKeyname()] = array();
        foreach($type->getParameterByKey("tags") as $tag)
        {
          $combinaisons[$type->getKeyname()][] = $tag;
        }
        if($type->getCanHasLink())
        {
          $combinaisons["link"] = array(
            "noLink",
            "internal",
            "external",
            "file"
          );
        }
      }
      elseif($type->getType() === "button")
      {
        $combinaisons[$type->getKeyname()] = array(
          "internal",
          "external",
          "file"
        );
        $combinaisons["graphicItem"] = null;
      }
      elseif($type->getType() === "switch")
      {
        $combinaisons[$type->getKeyname()] = array(
          true,
          false
        );
      }
    }
    return $combinaisons;
  }

  /**
   * combinaisons
   *
   * @param EditorComponentInterface $editorComponent
   *
   * @return array
   */
  protected function combinaisonDefaultValues(EditorComponentInterface $editorComponent): array
  {
    $defaultValues = array();
    /** @var EditorComponentTypeInterface $type */
    foreach ($editorComponent->getEditorComponentTypes() as $type)
    {
      if($type->getType() === "title")
      {
        $tags = array();
        foreach($type->getParameterByKey("tags") as $tag)
        {
          $tags[] = $tag;
        }
        if(count($tags) > 1 && in_array("h2", $tags))
        {
          $defaultValues[$type->getKeyname()] = "h2";
        }
        else
        {
          $defaultValues[$type->getKeyname()] = AustralTools::first($tags);
        }
        if($type->getCanHasLink())
        {
          $defaultValues[$type->getKeyname()] = "internal";
        }
      }
      elseif($type->getType() === "button")
      {
        $defaultValues[$type->getKeyname()] = "internal";
      }
      elseif($type->getType() === "switch")
      {
        $defaultValues[$type->getKeyname()] = true;
      }
    }
    return $defaultValues;
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
      "theme"     =>  $theme ? $theme->getKeyname() : "default",
      "keyname"   =>  $editorComponent->getKeyname(),
      "children"  =>  array(),
      "vars"      =>  array()
    );
  }

  /**
   * @param GuidelineEvent $guidelineEvent
   * @param EditorComponent $editorComponent
   * @param array $combinations
   * @param Layout|null $layout
   * @param Theme|null $theme
   * @param Option|null $option
   * @param array $optionsValue
   *
   * @return array
   * @throws \Exception
   */
  protected function generateComponent(GuidelineEvent $guidelineEvent, EditorComponent $editorComponent, ?Layout $layout = null, ?Theme $theme = null, ?Option $option = null, array $optionsValue = array()): array
  {
    /** @var Component $componentObject */
    $componentObject = clone $guidelineEvent->getComponentObject();
    $componentObject->setId(Uuid::uuid4()->toString());
    $componentObject->setEditorComponent($editorComponent);
    $componentObject->setLayoutId($layout ? $layout->getId() : null);
    $componentObject->setOptionId($option ? $option->getId() : null);
    $componentObject->setThemeId($theme ? $theme->getId() : null);

    $componentEvent = new ComponentEvent($guidelineEvent->getDefaultObjectPage(), $componentObject);
    $componentEvent->setIsGuideline(true);
    $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_HYDRATE);

    if(!$componentEvent->getIsDisabled())
    {
      return array(
        "id"                =>  $editorComponent->getId(),
        "keyname"           =>  $editorComponent->getKeyname(),
        "type"              =>  "default",
        "theme"             =>  $theme ? $theme->getKeyname() : "",
        "option"            =>  $option ? $option->getKeyname() : "",
        "layout"            =>  $layout ? $layout->getKeyname() : "",
        "templatePath"      =>  "Front\\{$editorComponent->getTemplatePathOrDefault()}",
        "values"            =>  $this->generateValues($editorComponent->getEditorComponentTypesWithChild(), $componentObject, $optionsValue),
        "vars"              =>  $componentEvent->getVars()
      );
    }
    return array();
  }

  /**
   * @param array $editorComponentTypes
   * @param Component $componentObject
   * @param array $optionsValue
   *
   * @return array
   */
  protected function generateValues(array $editorComponentTypes, Component $componentObject, array $optionsValue = array()): array
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
        $values[$type->getKeyname()]['tag'] = AustralTools::getValueByKey($optionsValue, $type->getKeyname(), null);
        $values[$type->getKeyname()]['value'] = $lipsum->words(4);
        if(array_key_exists("link", $optionsValue))
        {
          if(AustralTools::getValueByKey($optionsValue, "link", null) !== "noLink")
          {
            $linkType = AustralTools::getValueByKey($optionsValue, "link", null);
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
          $ulArray = array();
          for($line = 1; $line <= rand(0, 12); $line++)
          {
            $ulArray[] = "<li>{$lipsum->words(rand(4, 6))}</li>";
          }
          $ul = "";
          if(count($ulArray) > 0)
          {
            $ul = implode("", $ulArray);
            $ul = "<ul>{$ul}</ul>";
          }

          $paragraphFirstArray = array();
          for($p = 1; $p <= rand(1, 3); $p++)
          {
            $paragraphFirstArray[] = "<p>{$lipsum->words(rand(50, 100))}</p>";
          }
          $paragraphFirst = implode("", $paragraphFirstArray);

          $paragraphSecondArray = array();
          for($p = 1; $p <= rand(1, 2); $p++)
          {
            $paragraphSecondArray[] = "<p>{$lipsum->words(rand(50, 100))}</p>";
          }
          $paragraphSecond = implode("", $paragraphSecondArray);

          $values[$type->getKeyname()]['value'] = "{$paragraphFirst}{$ul}{$paragraphSecond}";
        }
      }
      elseif($type->getType() == "movie")
      {
        $values[$type->getKeyname()]['isIframe'] = $type->getParameterByKey("isIframe", false);
      }
      elseif($type->getType() == "button")
      {
        $values[$type->getKeyname()]['value'] = $lipsum->words(2);
        $linkType = AustralTools::getValueByKey($optionsValue, $type->getKeyname(), null);

        $values[$type->getKeyname()]["linkPicto"] = AustralTools::getValueByKey($optionsValue, "graphicItem", null);
      }
      elseif($type->getType() == "list" || $type->getType() == "group")
      {
        $values[$type->getKeyname()]['children'] = array();
      }
      elseif($type->getType() === "switch")
      {
        $values[$type->getKeyname()]["value"] =  (bool) AustralTools::getValueByKey($optionsValue, $type->getKeyname(), false);
      }
      elseif($type->getType() === "switch")
      {
        $values[$type->getKeyname()]["value"] = "austral-picto-company";
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

  /**
   * @var array
   */
  protected array $objectsRelations = array();

  /**
   * getObjectsByEntityClassAndId
   * @param string $entityClass
   * @param string $objectId
   * @return EntityInterface|null
   */
  protected function getObjectsByEntityClassAndId(string $entityClass, string $objectId): ?EntityInterface
  {
    $this->initialiseObjectsRelationsByEntityClass($entityClass);
    return AustralTools::getValueByKey(AustralTools::getValueByKey($this->objectsRelations, $entityClass), $objectId, null);
  }

  /**
   * initialiseObjectsRelations
   * @return ContentBlockSubscriber
   */
  protected function initialiseObjectsRelations(): ContentBlockSubscriber
  {
    /** @var EntityMapping $entityMapping */
    foreach ($this->mapping->getEntitiesMapping() as $entityMapping) {
      if ($entityMapping->getEntityClassMapping(ObjectContentBlockMapping::class)) {
        $this->initialiseObjectsRelationsByEntityClass($entityMapping->entityClass);
      }
    }
    return $this;
  }

  /**
   * initialiseObjectsRelations
   * @param $entityClass
   * @return ContentBlockSubscriber
   */
  protected function initialiseObjectsRelationsByEntityClass($entityClass): ContentBlockSubscriber
  {
    if($entityClass and !array_key_exists($entityClass, $this->objectsRelations))
    {
      $this->objectsRelations[$entityClass] = array();
      $objects = $this->selectObjectsRelations($entityClass);
      foreach ($objects as $object) {
        $this->objectsRelations[$entityClass][$object->getId()] = $object;
      }
    }
    return $this;
  }

  /**
   * selectObjectsRelations
   * @param string $entityClass
   * @return array
   */
  protected function selectObjectsRelations(string $entityClass): array
  {
    $objects = array();
    $repository = $this->entityManager->getRepository($entityClass);
    $translateMapping = $this->mapping->getEntityClassMapping($entityClass, EntityTranslateMapping::class);
    /** @var ObjectContentBlockMapping $objectContentBlock */
    $objectContentBlock = $this->mapping->getEntityClassMapping($entityClass, ObjectContentBlockMapping::class);
    if($objectContentBlock && ($repositoryFunction = $objectContentBlock->getRepositoryFunction()))
    {
      if(method_exists($repository, $repositoryFunction))
      {
        $objects = $repository->$repositoryFunction();
      }
    }
    if(!$objects && $objectContentBlock)
    {
      $objects = $repository->selectAll($objectContentBlock->getOrderBy(), $objectContentBlock->getOrderType(), function(AustralQueryBuilder $australQueryBuilder) use($translateMapping){
        if($translateMapping)
        {
          $australQueryBuilder->leftJoin("root.translates", "translates")->addSelect("translates");
        }
        $australQueryBuilder->indexBy("root", "root.id");
      });
    }
    return $objects;
  }



}