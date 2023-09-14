<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Form\Austral;


use Austral\AdminBundle\Module\Modules;
use Austral\ContentBlockBundle\Entity\Interfaces\LibraryInterface;
use Austral\ContentBlockBundle\Field\ContentBlockField;
use Austral\ContentBlockBundle\Form\Type\RestrictionFormType;
use Austral\ContentBlockBundle\Model\Editor\Restriction;
use Austral\ContentBlockBundle\Services\ContentBlockContainer;
use Austral\FormBundle\Field as Field;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\GroupFields;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints as Constraints;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Austral LibraryForm.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class LibraryForm
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var FormMapper $formMapper
   */
  protected FormMapper $formMapper;

  /**
   * @var string|null
   */
  protected ?string $formType = null;

  /**
   * @var IdentityTranslator
   */
  protected TranslatorInterface $translator;

  public function __construct(ContainerInterface $container, FormMapper $formMapper, ?string $formType = "library")
  {
    $this->container = $container;
    $this->formMapper = $formMapper;
    $this->formType = $formType;
    $this->translator = $this->container->get('translator');
  }

  /**
   * @return void
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   * @throws ReflectionException
   */
  public function form()
  {
    $this->formMapper->addFieldset("fieldset.right")
      ->setPositionName(Fieldset::POSITION_RIGHT)
      ->add(Field\ChoiceField::create("isEnabled", array(
        "choices.status.no"         =>  false,
        "choices.status.yes"        =>  true,
      )))
    ->end()
    ->addFieldset("fieldset.dev.config")
      ->setIsView($this->container->get("security.authorization_checker")->isGranted("ROLE_ROOT"))
      ->add(Field\TextField::create("keyname", array("autoConstraints" => false)))
    ->end()
    ->addFieldset("fieldset.generalInformation")
      ->add(Field\TextField::create("name"))
      ->add(Field\TextField::create("templatePath"))
      ->add(Field\TextField::create("css_class"))
      ->addGroup("imagePicto")
        ->add(Field\UploadField::create("image"))
      ->end()
    ->end();

    if($this->container->get("kernel")->getBundle("AustralGraphicItemsBundle"))
    {
      $this->formMapper->getFieldset("fieldset.generalInformation")
        ->addGroup("imagePicto")
          ->add(\Austral\GraphicItemsBundle\Field\GraphicItemField::create("graphicItem"))
        ->end();
    }


    if($this->formType !== "navigation")
    {
      $this->formMapper->addFieldset("fieldset.editorComponent.restrictions")
        ->add($this->createCollectionRestrictions())
      ->end();
    }
    $this->formMapper->addFieldset("fieldset.contentBlock")
      ->add(ContentBlockField::create())
    ->end();

    if($this->formType !== "navigation")
    {

      $this->formMapper
        ->addFieldset("fieldset.editorComponent.usedBy")
        ->setIsView($this->container->get("security.authorization_checker")->isGranted("ROLE_ROOT"))
        ->add(Field\TemplateField::create("usedBy", "@AustralContentBlock/Admin/EditorComponent/used-by.html.twig", array(), array(
          'componentsUsed'  =>  $this->componentsUsed()
        )))
        ->end();
    }
  }


  protected function componentsUsed()
  {
    /** @var LibraryInterface $library */
    $library = $this->formMapper->getObject();
    $allComponentsUsed = $this->container->get("austral.entity_manager.component")->selectArrayComponentsByLibrary($library);

    /** @var ContentBlockContainer $contentBlockContainer */
    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container');

    $objectsByEntity = $contentBlockContainer->getObjectsByEntity(true);

    /** @var Modules $australModules */
    $australModules = $this->container->get('austral.admin.modules');
    $componentsUsed = array();
    foreach($allComponentsUsed as $component)
    {
      if(!array_key_exists($component['objectLiaison'], $componentsUsed))
      {
        $objectClassname = $component['objectClassname'];
        if(array_key_exists($objectClassname, $objectsByEntity))
        {
          $objects = $objectsByEntity[$objectClassname];
          if(array_key_exists($component['objectId'], $objects))
          {
            $objectId = strpos($objectClassname, "Translate") !== false ? $objects[$component['objectId']]->getMaster()->getId(): $objects[$component['objectId']]->getId();
            $objectClassname = str_replace("Translate", "", $objectClassname);
            $componentsUsed[$component['objectLiaison']] = array(
              "objectClassname"     =>  $objectClassname,
              "objectId"            =>  $objectId,
              "module"              =>  ""//$australModules->getModuleByEntityClassname($objectClassname)[0]
            );
          }
        }
      }
    }
    return $componentsUsed;
  }

  /**
   * @return Field\CollectionEmbedField
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  protected function createCollectionRestrictions(): Field\CollectionEmbedField
  {
    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container');

    $contentBlockContainerSelect = array(
      "all"   => $contentBlockContainerSelect["all"] = array(
        $this->translator->trans("choices.restriction.all", array('%element%'=>"All"),$this->formMapper->getTranslateDomain())    =>  "all:all"
      )
    );
    $contentBlockContainerData = array(
      "all"   =>  "element-view-all"
    );
    foreach($contentBlockContainer->getObjectsByEntity() as $entityName => $objects)
    {
      if($entityName === "Library")
      {
        $contentBlockContainerData["{$entityName}Navigation"] = "element-view-{$entityName}";
        $contentBlockContainerSelect["{$entityName}Navigation"] = array(
          $this->translator->trans("choices.restriction.all", array('%element%'=>"{$entityName}Navigation"),$this->formMapper->getTranslateDomain())    =>  "{$entityName}Navigation:all"
        );
      }

      $contentBlockContainerData["{$entityName}"] = "element-view-{$entityName}";
      $contentBlockContainerSelect[$entityName] = array(
        $this->translator->trans("choices.restriction.all", array('%element%'=>$entityName),$this->formMapper->getTranslateDomain())    =>  "{$entityName}:all"
      );
      foreach($objects as $object)
      {
        if($entityName === "Library")
        {
          if($object->getIsNavigationMenu())
          {
            $contentBlockContainerSelect["{$entityName}Navigation"][$object->__toString()] = "{$entityName}Navigation:{$object->getId()}";
          }
          else
          {
            $contentBlockContainerSelect[$entityName][$object->__toString()] = "{$entityName}:{$object->getId()}";
          }
        }
        else
        {
          $contentBlockContainerSelect[$entityName][$object->__toString()] = "{$entityName}:{$object->getId()}";
        }
      }
    }

    $configContainerByEntity = $this->container->get('austral.content_block.config')->getConfig('container_by_entity');
    $containerNameByEntities = array();
    foreach($contentBlockContainer->getEntitiesWithReelName() as $entityName => $classname)
    {
      $containerNameByEntities[$entityName] = $this->container->get('austral.entity_manager.component')->selectArrayComponentsContainerNameByClassname($classname);
      if(array_key_exists($entityName, $configContainerByEntity)) {
        $containerNameByEntities[$entityName] = array_merge($containerNameByEntities[$entityName], $configContainerByEntity[$entityName]);
      }

      if($entityName === "Library")
      {
        $containerNameByEntities["{$entityName}Navigation"] = $containerNameByEntities[$entityName];
        if(array_key_exists($entityName, $configContainerByEntity)) {
          $containerNameByEntities["{$entityName}Navigation"] = array_merge($containerNameByEntities["{$entityName}Navigation"], $configContainerByEntity[$entityName]);
        }
      }
    }


    $restrictionFormMapper = new FormMapper();
    $restriction = new Restriction();
    $restriction->setCondition("exclude");
    $restrictionFormMapper->setObject($restriction)
      ->addGroup("generalInformations")
      ->add(Field\SelectField::create("value",
          $contentBlockContainerSelect,
          array(
            "entitled"  =>  false,
            "group"       =>  array(
              'size'  => GroupFields::SIZE_COL_5
            ),
            "attr"        =>  array(
              "data-view-by-choices-parent"   =>  ".collection-embed-element",
              'data-view-by-choices-regex'    =>  "(\w+):.*",
              'data-view-by-choices'      =>  json_encode($contentBlockContainerData)
            )
          )
        )->addConstraint(new Constraints\NotNull())
      )
      ->add(Field\ChoiceField::create("condition", array(
        "choices.restriction.exclude"     =>  array(
          "value"   =>  "exclude",
        ),
        "choices.restriction.include"         =>  array(
          "value"   =>  "include",
          "styles"  =>  array(
            "--element-choice-current-background:var(--color-blue-20)",
            "--element-choice-current-color:var(--color-blue-100)",
            "--element-choice-hover-color:var(--color-blue-100)"
          )
        ),
      ), array(
        "entitled"        => false,
        "choice_style"  =>  "full",
        "group"     =>  array(
          "size"      =>  GroupFields::SIZE_COL_4
        )
      ),
      )->addConstraint(new Constraints\NotNull()))
      ->end()
      ->add(Field\SymfonyField::create("position", HiddenType::class, array("entitled"=>false, "attr"=>array("data-collection-sortabled"=>""))));

    $group = $restrictionFormMapper->addGroup("generalInformations")
      ->addGroup("containerName")
      ->setSize(GroupFields::SIZE_COL_3)
      ->setDirection(GroupFields::DIRECTION_COLUMN);


    $allContainerNames = array(
      $this->translator->trans("choices.restriction.containerName.all", array(), $restrictionFormMapper->getTranslateDomain())    =>  "all",
      "master"  =>  "master"
    );
    foreach ($containerNameByEntities as $entityName => $containerNames)
    {
      foreach($containerNames as $containerName)
      {
        $allContainerNames[$containerName] = $containerName;
      }
    }

    $group->add(Field\SelectField::create("containerNameAll",
      $allContainerNames,
      array(
        "entitled"  =>  false,
        "getter"    =>  function(Restriction $object) {
          return $object->getContainerName();
        },
        "setter"    =>  function(Restriction $object, $value) {
          if(strpos($object->getValue(), "all") !== false)
          {
            $object->setContainerName($value);
          }
        },
        "container" =>  array('class'=>"view-element-by-choices element-view-all")
      )
    )->addConstraint(new Constraints\NotNull()))
      ->end();

    foreach($containerNameByEntities as $entityName => $containerNames)
    {
      $containerNamesSelect = array($this->translator->trans("choices.restriction.containerName.all", array('%element%'=>$entityName), $this->formMapper->getTranslateDomain())    =>  "all");
      foreach($containerNames as $containerName)
      {
        $containerNamesSelect[$containerName] = $containerName;
      }
      $group->add(Field\SelectField::create("containerName{$entityName}",
        $containerNamesSelect,
        array(
          "entitled"  =>  false,
          "getter"    =>  function(Restriction $object) {
            return $object->getContainerName();
          },
          "setter"    =>  function(Restriction $object, $value) use($entityName) {
            if(strpos($object->getValue(), $entityName) !== false)
            {
              $object->setContainerName($value);
            }
          },
          "container" =>  array('class'=>"view-element-by-choices element-view-{$entityName}")
        )
      )->addConstraint(new Constraints\NotNull())
      )

        ->end();
    }

    $this->formMapper->addSubFormMapper("restrictions", $restrictionFormMapper);
    /** @var RestrictionFormType $restrictionFormType */
    $restrictionFormType = $this->container->get('austral.content_block.restriction_form_type')->setFormMapper($restrictionFormMapper);
    return Field\CollectionEmbedField::create("restrictions", array(
        "button"              =>  "button.new.restriction",
        "entitled"            =>  false,
        "collections"         =>  array(
          "objects"             =>  "restrictions"
        ),
        "allow"               =>  array(
          "child"               =>  false,
          "add"                 =>  true,
          "delete"              =>  true,
        ),
        "entry"               =>  array(
          "type"                =>  get_class($restrictionFormType)
        ),
        "prototype"           =>  array(
          "data"                =>  $restriction
        ),
        "sortable"            =>  array(
          "value"               =>  "position",
          "editable"            =>  true
        ),
      )
    );
  }

}