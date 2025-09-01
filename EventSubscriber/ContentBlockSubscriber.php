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

use App\Entity\Austral\ContentBlockBundle\EditorComponentType;
use Austral\ContentBlockBundle\Entity\Component;
use Austral\ContentBlockBundle\Entity\ComponentValue;
use Austral\ContentBlockBundle\Entity\ComponentValues;
use Austral\ContentBlockBundle\Entity\EditorComponent;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Event\ComponentEvent;
use Austral\ContentBlockBundle\Event\ContentBlockEvent;
use Austral\ContentBlockBundle\Mapping\ObjectContentBlockMapping;
use Austral\ContentBlockBundle\Mapping\ObjectContentBlocksMapping;
use Austral\ContentBlockBundle\Services\ContentBlockContainer;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\QueryException;
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
   * @var Generator|null
   */
  protected ?Generator $fileLinkGenerator;

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
   * @param Generator|null $fileLinkGenerator
   * @param UrlParameterManagement|null $urlParameterManagement
   */
  public function __construct(ContentBlockContainer $contentBlockContainer,
    EventDispatcherInterface $dispatcher,
    Mapping $mapping,
    EntityManager $entityManager,
    ?Generator $fileLinkGenerator,
    ?UrlParameterManagement $urlParameterManagement = null
  )
  {
    $this->mapping = $mapping;
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
    $this->contentBlockContainer = $contentBlockContainer;
    $this->fileLinkGenerator = $fileLinkGenerator;
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
      ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_HYDRATE     =>  ["componentInit", 1024],
    ];
  }

  /**
   * componentsInit
   *
   * @param ContentBlockEvent $contentBlockEvent
   * @return void
   */
  public function componentsInit(ContentBlockEvent $contentBlockEvent)
  {
    $this->contentBlockContainer->initComponentByObject($contentBlockEvent->getObject(), false);
    /** @var Component $componentObject */
    foreach($contentBlockEvent->getObject()->getComponents() as $componentObjects)
    {
      foreach($componentObjects as $componentObject)
      {
        $componentEvent = new ComponentEvent($contentBlockEvent->getObject(), $componentObject);
        $componentEvent->setIsGuideline($contentBlockEvent->getIsGuidelineBuild());
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

    $blockDefaultKey = 0;
    $currentContainerId = null;
    /** @var Component $componentObject */
    foreach($contentBlockEvent->getObject()->getComponents() as $containerName => $componentObjects)
    {
      $blockName = "default-{$blockDefaultKey}";

      $finalComponentsByContainer = array($blockName => array(
        "keyname"             =>  "default",
        "containerKeyname"    =>  "default",
        "container"           =>  "default",
        "theme"               =>  "",
        "option"              =>  "",
        "layout"              =>  "",
        "children"            => array()
      ));
      $finalComponentsByContainerByTypes = array($blockName => array(
        "keyname"   =>  "default",
        "containerKeyname"   =>  "default",
        "children"  => array()
      ));
      foreach($componentObjects as $componentObject)
      {
        if($componentObject->getComponentType() === "library")
        {
          $componentContainerChildId = null;
          if($componentContainerId = $componentObject->getContainerId())
          {
            if(str_contains($componentContainerId, "_"))
            {
              list($componentContainerId, $componentContainerChildId) = explode("_", $componentContainerId);
            }
          }
          /** @var LibraryInterface $library */
          $library = $componentObject->getLibrary();

          if($currentContainerId && $componentContainerId !== $currentContainerId) {
            $blockDefaultKey++;
            $blockName = "library-{$blockDefaultKey}";
            $currentContainerId = $library->getId();
            $libraryHasContainer = false;
            foreach($library->getComponents() as $libraryContainerName => $libraryComponents)
            {
              /** @var Component $libraryComponent */
              foreach($libraryComponents as $libraryComponent)
              {
                if($libraryComponent->getEditorComponent()?->getIsContainer())
                {
                  $libraryHasContainer = true;
                }
              }
            }
            $finalComponentsByContainer[$blockName] = array(
              "keyname" => "library",
              "containerKeyname" => "library",
              "container" => $libraryHasContainer ? "library-with-container" : "library",
              "theme" => "library",
              "option" => "",
              "layout" => "",
              "children" => array()
            );
            $finalComponentsByContainerByTypes[$blockName] = array(
              "keyname" => "library",
              "containerKeyname" => "library",
              "children" => array()
            );
          }

          if($library->getAccessibleInContent() && $library->getIsEnabled())
          {
            $componentValues = array(
              "id"                =>  $componentObject->getId(),
              "type"              =>  "library",
              "keyname"           =>  $componentObject->getLibrary()->getKeyname(),
            );
            if($componentContainerChildId)
            {
              $finalComponentsByContainer[$blockName]['children'][$componentContainerChildId]["children"]["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
              $finalComponentsByContainerByTypes[$blockName]['children'][$componentContainerChildId]["children"][$componentObject->getLibrary()->getKeyname()][] = $componentValues;
            }
            else
            {
              $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
              $finalComponentsByContainerByTypes[$blockName]['children'][$componentObject->getLibrary()->getKeyname()][] = $componentValues;
            }
          }
        }
        else
        {
          $componentEvent = new ComponentEvent($contentBlockEvent->getObject(), $componentObject);
          $componentEvent->setIsGuideline($contentBlockEvent->getIsGuidelineBuild());
          $this->dispatcher->dispatch($componentEvent, ComponentEvent::EVENT_AUSTRAL_CONTENT_BLOCK_COMPONENT_HYDRATE);
          if(!$componentEvent->getIsDisabled())
          {
            if($componentObject->getEditorComponent()->getIsEnabled())
            {
              if($componentObject->getEditorComponent()->getIsContainer())
              {
                $currentContainerId = $componentObject->getId();
                $keynameTemplate = $componentObject->getThemeKeyname() ?? $componentObject->getKeyname();
                $blockName = "{$keynameTemplate}-{$componentObject->getId()}";
                $finalComponentsByContainer[$blockName] = array(
                  "id"                =>  $componentObject->getId(),
                  "theme"             =>  $componentObject->getThemeKeyname(),
                  "option"            =>  $componentObject->getOptionKeyname(),
                  "layout"            =>  $componentObject->getLayoutKeyname(),
                  "type"              =>  "default",
                  "isContainer"       =>  true,
                  "container"         =>  $componentObject->getEditorComponent()->hasContainerChildren() ? "row" : "default",
                  "containerKeyname"  =>  $componentObject->getEditorComponent()->getKeyname(),
                  "keyname"           =>  $componentObject->getKeyname(),
                  "children"          =>  array(),
                  "vars"              =>  $componentEvent->getVars(),
                  "values"            =>  $this->componentValues($componentObject->getComponentValues()),
                );

                if($componentObject->getEditorComponent()->hasContainerChildren())
                {
                  /** @var EditorComponentType $editorComponentType */
                  foreach($componentObject->getEditorComponent()->getEditorComponentTypes() as $editorComponentType)
                  {
                    if($editorComponentType->getParameterByKey("hasChildren"))
                    {
                      $finalComponentsByContainer[$blockName]["children"][$editorComponentType->getId()] = array(
                        "keyname"   =>  $editorComponentType->getkeyname(),
                        "container" =>  "col",
                        "children"  =>  array()
                      );
                    }
                  }
                }
              }
              else
              {
                $componentContainerChildId = null;
                if($componentContainerId = $componentObject->getContainerId())
                {
                  if(str_contains($componentContainerId, "_"))
                  {
                    list($componentContainerId, $componentContainerChildId) = explode("_", $componentContainerId);
                  }
                }

                if($currentContainerId && $componentContainerId !== $currentContainerId)
                {
                  $blockDefaultKey++;
                  $blockName = "default-{$blockDefaultKey}";
                  $currentContainerId = null;

                  $finalComponentsByContainer[$blockName] = array(
                    "keyname"             =>  "default",
                    "containerKeyname"    =>  "default",
                    "container"           =>  "default",
                    "theme"               =>  "",
                    "option"              =>  "",
                    "layout"              =>  "",
                    "children"            => array()
                  );
                  $finalComponentsByContainerByTypes[$blockName] = array(
                    "keyname"             =>  "default",
                    "containerKeyname"    =>  "default",
                    "children"            => array()
                  );
                }
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

                if($componentContainerChildId)
                {
                  $finalComponentsByContainer[$blockName]['children'][$componentContainerChildId]["children"]["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
                  $finalComponentsByContainerByTypes[$blockName]['children'][$componentContainerChildId]["children"][$componentObject->getEditorComponent()->getKeyname()][] = $componentValues;
                }
                else
                {
                  $finalComponentsByContainer[$blockName]['children']["{$componentObject->getPosition()}-{$componentObject->getId()}"] = $componentValues;
                  $finalComponentsByContainerByTypes[$blockName]['children'][$componentObject->getEditorComponent()->getKeyname()][] = $componentValues;
                }

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
   * @throws \Exception
   */
  protected function componentValues(Collection $componentValues): array
  {
    $values = array();
    /** @var ComponentValue $componentValueObject */
    foreach($componentValues as $componentValueObject)
    {
      /** @var EditorComponentType $editorComponentType */
      $editorComponentType = $componentValueObject->getEditorComponentType();

      /** @var EditorComponent $editorComponent */
      $editorComponent = $editorComponentType->getEditorComponent();

      $values[$editorComponentType->getKeyname()] = array(
        "id"        =>  $componentValueObject->getId(),
        "type"      =>  $editorComponentType->getType(),
        "classCss"  =>  $componentValueObject->getOptionsByKey("classCss", null)
      );
      if($editorComponentType->getType() == "image" || $editorComponentType->getType() == "file")
      {
        $values[$editorComponentType->getKeyname()] = $componentValueObject;
      }
      else if ($editorComponentType->getType() == "choice") {
        $values[$editorComponentType->getKeyname()]['value'] = $componentValueObject->getOptionsByKey("choice");
      }
      else if ($editorComponentType->getType() == "text" && $editorComponentType->getParameterByKey("type") === "date") {
        $values[$editorComponentType->getKeyname()]['value'] = "";
        $values[$editorComponentType->getKeyname()]['date'] = $componentValueObject->getDate();
      }
      else
      {
        $values[$editorComponentType->getKeyname()]['value'] = $componentValueObject->getContent();
      }
      if($tag = $componentValueObject->getOptionsByKey("tags", null))
      {
        $values[$editorComponentType->getKeyname()]['tag'] = $tag;
      }
      if($editorComponentType->getType() == "textarea")
      {
        $values[$editorComponentType->getKeyname()]['isWysiwyg'] = $editorComponentType->getParameterByKey("isWysiwyg");
      }
      if($editorComponentType->getType() == "object")
      {
        if($objectId = $componentValueObject->getOptionsByKey("objectId"))
        {
          $objectContentBlockName = null;
          $entityClass = $editorComponentType->getParameterByKey("entityClass");
          if($entityClass === "all")
          {
            list($entityClass, $objectId) = explode("::", $objectId);
          }
          elseif (str_contains($entityClass, "::"))
          {
            list($objectContentBlockName, $entityClass) = explode("::", $entityClass);
          }
          $values[$editorComponentType->getKeyname()]['objectId'] = "{$entityClass}::{$objectId}";
          if($editorComponent->getAutoHydrate())
          {
            $object = $this->getObjectsByEntityClassAndId($entityClass, $objectId, $objectContentBlockName);
            $values[$editorComponentType->getKeyname()]['object'] = $object;
            $values[$editorComponentType->getKeyname()]['value'] = $object ? $object->__toString() : "";
          }
          else
          {
            $values[$editorComponentType->getKeyname()]['object'] = null;
            $values[$editorComponentType->getKeyname()]['value'] = "";
          }
        }
      }
      if($editorComponentType->getType() == "movie")
      {
        $values[$editorComponentType->getKeyname()]['isIframe'] = $editorComponentType->getParameterByKey("isIframe");
        if($videoUrl = $componentValueObject->getContent())
        {
          if(strpos($videoUrl, "youtube") || str_contains($videoUrl, "youtu."))
          {
            if(str_contains($videoUrl, "youtu."))
            {
              preg_match('/youtu.[\w]{0,}\/([\w|-]{0,})/', $videoUrl, $matches);
              $videoId = AustralTools::getValueByKey($matches, 1, null);
            }
            else
            {
              preg_match('/(v=|embed\/)([\w|-]{0,})/', $videoUrl, $matches);
              $videoId = AustralTools::getValueByKey($matches, 2, null);
            }
            $videoInfos = array(
              "type"              =>  "youtube",
              "key"               =>  $videoId,
              "url"               =>  "https://www.youtube-nocookie.com/embed/{$videoId}",
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

            $vimeoInfos = $this->retrieveVimeoInfo($videoId);
            if($thumbnailPath = AustralTools::getValueByKey($vimeoInfos, "thumbnail_small", null))
            {
              $thumbnailPath = preg_replace("/-d_(.*)/", "-d_", $thumbnailPath);
            }
            $videoInfos = array(
              "type"              =>  "vimeo",
              "key"               =>  $videoId,
              "url"               =>  "https://player.vimeo.com/video/{$videoId}",
              "title"             =>  AustralTools::getValueByKey($vimeoInfos, "title", ""),
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
          $values[$editorComponentType->getKeyname()]['video'] = $videoInfos;
        }
      }
      if($editorComponentType->getType() == "button")
      {
        $values[$editorComponentType->getKeyname()]["linkPicto"] = $componentValueObject->getLinkPicto();
      }
      
      if($linkType = $componentValueObject->getLinkType())
      {
        $values[$editorComponentType->getKeyname()]["link"] = array(
          "anchor"  =>  $componentValueObject->getOptionsByKey("anchor", null),
          "target"  =>  $componentValueObject->getOptionsByKey("target", null),
          "title"   =>  $componentValueObject->getOptionsByKey("title", null),
          "url"     =>  $linkType == "internal" ? "" : $componentValueObject->getLinkUrl(),
          "type"    =>  $linkType,
        );
        if($linkType == "internal")
        {
          if($this->urlParameterManagement && $componentValueObject->getLinkEntityKey()) {
            $values[$editorComponentType->getKeyname()]["link"]['url'] = "#INTERNAL_LINK_{$componentValueObject->getLinkEntityKey()}#";
            $separator = ":";
            if(str_contains($componentValueObject->getLinkEntityKey(), "::"))
            {
              $separator = "::";
            }
            list($entity, $id) = explode($separator, $componentValueObject->getLinkEntityKey());
            $urlParameter = $this->urlParameterManagement->getUrlParameterByObjectClassnameAndId($entity,$id);
            $values[$editorComponentType->getKeyname()]["link"]["urlParameter"] = $urlParameter;
            if(!$values[$editorComponentType->getKeyname()]["value"])
            {
              $values[$editorComponentType->getKeyname()]["value"] = $urlParameter->getObject()?->__toString();
            }
          }
        }
        elseif($linkType == "external")
        {
          $linkUrl = $componentValueObject->getLinkUrl();
          if (!u($linkUrl)->ignoreCase()->startsWith(array("https://", "http://", "javascript:", "%")) && (strpos($linkUrl, "INTERNAL_LINK_") === false)) {
            $linkUrl = "//{$linkUrl}";
          }
          $values[$editorComponentType->getKeyname()]["link"]['url'] = $linkUrl;
        }
        elseif($linkType == "file")
        {
          if($this->fileLinkGenerator)
          {
            $values[$editorComponentType->getKeyname()]["link"]['url'] = $this->fileLinkGenerator
              ->download($componentValueObject, "file");
          }
          $values[$editorComponentType->getKeyname()]["link"]['file'] = $componentValueObject;
        }
        elseif($linkType == "phone")
        {
          $values[$editorComponentType->getKeyname()]["link"]['url'] = "tel:{$componentValueObject->getLinkPhone()}";
        }
        elseif($linkType == "email")
        {
          $values[$editorComponentType->getKeyname()]["link"]['url'] = "mailto:{$componentValueObject->getLinkemail()}";
        }
      }
      if($editorComponentType->getType() == "list" || $editorComponentType->getType() == "group")
      {
        if($children = $componentValueObject->getChildren()->toArray())
        {
          $childrenValues = array();
          /** @var ComponentValues $child */
          foreach ($children as $child)
          {
            if($editorComponentType->getType() == "group")
            {
              $childrenValues = $this->componentValues($child->getChildren());
            }
            else
            {
              $childrenValues[$child->getPosition()] = $this->componentValues($child->getChildren());
            }
          }
          $values[$editorComponentType->getKeyname()]["children"] = $childrenValues;
        }
      }
    }
    return $values;
  }

  /**
   * retrieveVimeoInfo
   *
   * @param $videoId
   * @return array
   */
  protected function retrieveVimeoInfo($videoId): array
  {
    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_URL, "https://vimeo.com/api/v2/video/{$videoId}.php");
      $data = curl_exec($ch);
      curl_close($ch);
    } catch (\Exception $e) {
      $data = "";
    }
    if($data) {
      $vimeoInfos = unserialize($data);
      return AustralTools::first($vimeoInfos);
    }
    return array();
  }

  /**
   * @var array
   */
  protected array $objectsRelations = array();

  /**
   * getObjectsByEntityClassAndId
   *
   * @param string $entityClass
   * @param string $objectId
   * @param string|null $objectContentBlockName
   * @return EntityInterface|null
   * @throws QueryException
   */
  protected function getObjectsByEntityClassAndId(string $entityClass, string $objectId, ?string $objectContentBlockName = null): ?EntityInterface
  {
    $this->initialiseObjectsRelationsByEntityClass($entityClass, $objectContentBlockName);
    return AustralTools::getValueByKey(AustralTools::getValueByKey($this->objectsRelations, $entityClass), $objectId, null);
  }

  /**
   * initialiseObjectsRelations
   *
   * @return ContentBlockSubscriber
   * @throws QueryException
   */
  protected function initialiseObjectsRelations(): ContentBlockSubscriber
  {
    /** @var EntityMapping $entityMapping */
    foreach ($this->mapping->getEntitiesMapping() as $entityMapping) {
      /** @var ObjectContentBlocksMapping $objectContentBlocks */
      if($objectContentBlocks = $entityMapping->getEntityClassMapping(ObjectContentBlocksMapping::class) )
      {
        /** @var ObjectContentBlockMapping $objectContentBlockMapping */
        foreach ($objectContentBlocks->getObjectContentBlocksMapping() as $objectContentBlockMapping)
        {
          $this->initialiseObjectsRelationsByEntityClass($entityMapping->entityClass, $objectContentBlockMapping->getName());
        }
      }
      elseif($entityMapping->getEntityClassMapping(ObjectContentBlockMapping::class))
      {
        $this->initialiseObjectsRelationsByEntityClass($entityMapping->entityClass);
      }
    }
    return $this;
  }

  /**
   * initialiseObjectsRelations
   *
   * @param $entityClass
   * @param string|null $objectContentBlockName
   * @return ContentBlockSubscriber
   * @throws QueryException
   */
  protected function initialiseObjectsRelationsByEntityClass($entityClass, ?string $objectContentBlockName = null): ContentBlockSubscriber
  {
    if($entityClass and !array_key_exists($entityClass, $this->objectsRelations))
    {
      $this->objectsRelations[$entityClass] = array();
      $objects = $this->contentBlockContainer->selectObjectsRelations($entityClass, $objectContentBlockName);
      foreach ($objects as $object) {
        $this->objectsRelations[$entityClass][$object->getId()] = $object;
      }
    }
    return $this;
  }

}