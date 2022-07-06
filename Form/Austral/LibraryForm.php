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


use Austral\ContentBlockBundle\Field\ContentBlockField;
use Austral\ContentBlockBundle\Form\Type\RestrictionFormType;
use Austral\ContentBlockBundle\Model\Editor\Restriction;
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
      ->addGroup("generalInformation")
        ->addGroup("left", null)
          ->setSize(GroupFields::SIZE_COL_6)
          ->setDirection(GroupFields::DIRECTION_COLUMN)
          ->add(Field\TextField::create("name"))
          ->add(Field\TextField::create("templatePath"))
          ->add(Field\TextField::create("css_class"))
        ->end()
        ->addGroup("right")
          ->setSize(GroupFields::SIZE_COL_6)
          ->setDirection(GroupFields::DIRECTION_COLUMN)
          ->add(Field\UploadField::create("image"))
        ->end()
      ->end()
    ->end();

    if($this->formType !== "navigation")
    {
      $this->formMapper->addFieldset("fieldset.editorComponent.restrictions")
        ->add($this->createCollectionRestrictions())
      ->end();
    }
    $this->formMapper->addFieldset("fieldset.contentBlock")
      ->add(ContentBlockField::create())
    ->end();
  }


  /**
   * @return Field\CollectionEmbedField
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  protected function createCollectionRestrictions(): Field\CollectionEmbedField
  {
    $contentBlockContainer = $this->container->get('austral.content_block.content_block_container');

    $contentBlockContainerSelect = array();
    $contentBlockContainerData = array();
    foreach($contentBlockContainer->getObjectsByEntity() as $entityName => $objects)
    {
      $contentBlockContainerData["{$entityName}"] = "element-view-{$entityName}";
      $contentBlockContainerSelect[$entityName] = array(
        $this->translator->trans("choices.restriction.all", array('%element%'=>$entityName), $this->formMapper->getTranslateDomain())    =>  "{$entityName}:all"
      );
      foreach($objects as $object)
      {
        $contentBlockContainerSelect[$entityName][$object->__toString()] = "{$entityName}:{$object->getId()}";
      }
    }


    $containerNameByEntities = array();
    foreach($contentBlockContainer->getEntitiesWithReelName() as $entityName => $classname)
    {
      $containerNameByEntities[$entityName] = $this->container->get('austral.entity_manager.component')->selectArrayComponentsContainerNameByClassname($classname);
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