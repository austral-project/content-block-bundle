<?php
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ContentBlockBundle\Repository;

use App\Entity\Austral\ContentBlockBundle\ComponentValue;
use App\Entity\Austral\ContentBlockBundle\ComponentValues;
use App\Entity\Austral\ContentBlockBundle\EditorComponent;
use App\Entity\Austral\ContentBlockBundle\EditorComponentType;

use Austral\ContentBlockBundle\Entity\Interfaces\EditorComponentInterface;
use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\EntityBundle\Repository\EntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Austral Component Repository.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ComponentRepository extends EntityRepository
{

  /**
   * @param string $classname
   *
   * @return array
   */
  public function selectArrayComponentsContainerNameByClassname(string $classname): array
  {
    $queryBuilder = $this->createQueryBuilder('root', "root.objectContainerName")
      ->select('root.objectContainerName');
    $queryBuilder->where("root.objectClassname = :objectClassname")
      ->setParameters(array(
        "objectClassname" =>  $classname
      ));

    $queryBuilder->groupBy("root.objectContainerName");
    $query = $queryBuilder->getQuery();
    try {
      $results = $query->getArrayResult();
    } catch (NoResultException $e) {
      $results = array();
    }
    return array_keys($results);
  }

  /**
   * @return array
   * @throws QueryException
   */
  public function selectComponentsByObjectsIds(): array
  {
    $queryBuilder = $this->queryBuilderComponents();
    $queryBuilder->orderBy("root.position", "ASC");
    $queryBuilder->indexBy("root", "root.id");
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }

  /**
   * queryBuilderComponents
   * @return AustralQueryBuilder
   */
  protected function queryBuilderComponents(): AustralQueryBuilder
  {
    $queryBuilder = $this->createQueryBuilder('root');
    $queryBuilder->leftJoin("root.editorComponent", "editorComponent")->addSelect("editorComponent")
      ->leftJoin("editorComponent.editorComponentTypes", "editorComponentChildren")->addSelect("editorComponentChildren")
      ->leftJoin("root.library", "library")->addSelect("library")
      ->leftJoin("library.translates", "libraryTranslates")->addSelect("libraryTranslates")
      ->leftJoin("root.componentValues", "componentValues")->addSelect("componentValues")
      ->leftJoin("componentValues.editorComponentType", "editorComponentType")->addSelect("editorComponentType")

      ->leftJoin("componentValues.children", "children")->addSelect("children")
      ->leftJoin("children.children", "componentValuesChildren")->addSelect("componentValuesChildren")
      ->leftJoin("componentValuesChildren.editorComponentType", "editorComponentType2")->addSelect("editorComponentType2")

      ->leftJoin("componentValuesChildren.children", "children2")->addSelect("children2")
      ->leftJoin("children2.children", "componentValuesChildren2")->addSelect("componentValuesChildren2")
      ->leftJoin("componentValuesChildren2.editorComponentType", "editorComponentType3")->addSelect("editorComponentType3")

      ->leftJoin("componentValuesChildren2.children", "children3")->addSelect("children3")
      ->leftJoin("children3.children", "componentValuesChildren3")->addSelect("componentValuesChildren3")
      ->leftJoin("componentValuesChildren3.editorComponentType", "editorComponentType4")->addSelect("editorComponentType4")

      ->leftJoin("componentValuesChildren3.children", "children4")->addSelect("children4")
      ->leftJoin("children4.children", "componentValuesChildren4")->addSelect("componentValuesChildren4")
      ->leftJoin("componentValuesChildren4.editorComponentType", "editorComponentType5")->addSelect("editorComponentType5");
    
    return $queryBuilder;
  }

  /**
   * @param $objectId
   * @param string $classname
   *
   * @return array
   * @throws QueryException
   */
  public function selectComponentsByObjectIdAndClassname($objectId, string $classname): array
  {
    $queryBuilder = $this->queryBuilderComponents();
    $queryBuilder->where("root.objectId = :objectId")
      ->andWhere("root.objectClassname = :objectClassname")
      ->setParameters(array(
        "objectId"        =>  $objectId,
        "objectClassname" =>  $classname
      ));
    $queryBuilder->orderBy("root.position", "ASC");
    $queryBuilder->indexBy("root", "root.id");
    $query = $queryBuilder->getQuery();
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }

  /**
   * @param EditorComponentInterface $editorComponent
   *
   * @return array
   * @throws QueryException
   */
  public function selectArrayComponentsByEditorComponent(EditorComponentInterface $editorComponent): array
  {
    $queryBuilder = $this->createQueryBuilder('root')
      ->select("root.id, root.objectClassname, root.objectId, editorComponent.id, CONCAT(root.objectClassname, '_',root.objectId) as objectLiaison");
    $queryBuilder->leftJoin("root.editorComponent", "editorComponent")
      ->where("editorComponent.id = :editorComponentId")
      ->setParameters(array(
        "editorComponentId"        =>  $editorComponent->getId(),
      ));
    $queryBuilder->indexBy("root", "root.id");
    $query = $queryBuilder->getQuery();
    try {
      $results = $query->getArrayResult();
    } catch (NoResultException $e) {
      $results = array();
    }
    return $results;
  }

  /**
   * @param $objectId
   * @param string $classname
   *
   * @return Collection|array
   */
  public function selectComponentsByObjectIdAndClassnameFull($objectId, string $classname)
  {
    $selectMaster = array();
    $select = array();
    $select2 = array();
    $rsm = new ResultSetMapping();

    /*
     * Add Add relation and columnName to Component
     */
    $rsm->addEntityResult($this->_entityName, 'acb_c');
    foreach ($this->getEntityManager()->getClassMetadata($this->_entityName)->fieldMappings as $field) {
      $rsm->addFieldResult('acb_c', "acb_c_{$field['columnName']}", $field['fieldName']);
      $selectMaster[] = "acb_c.{$field['columnName']} as acb_c_{$field['columnName']}";
    }
    $selectMaster[] = "acb_c.editor_component_id as acb_c_editor_component_id";

    /*
     * Add Add relation and columnName to EditorComponent
     */
    $rsm->addJoinedEntityResult(EditorComponent::class, "acb_ec", "acb_c", "editorComponent");
    $rsm->addJoinedEntityResult(EditorComponent::class, "acb_ec_master", "acb_c", "editorComponent");
    foreach ($this->getEntityManager()->getClassMetadata(EditorComponent::class)->fieldMappings as $field) {
      $rsm->addFieldResult('acb_ec', "acb_ec_{$field['columnName']}", $field['fieldName']);
      $rsm->addFieldResult('acb_ec_master', "acb_ec_master_{$field['columnName']}", $field['fieldName']);
      $selectMaster[] = "acb_ec_master.{$field['columnName']} as acb_ec_master_{$field['columnName']}";

      $select[] = "acb_ec.{$field['columnName']} as acb_ec_{$field['columnName']} ";
      $select2[] = "acb_ec_1.{$field['columnName']} as acb_ec_1_{$field['columnName']}";
    }


    /*
     * Add Add relation and columnName to ComponentValue
     */
    $rsm->addJoinedEntityResult(ComponentValue::class, "acb_cv", "acb_c", "componentValues");
    foreach ($this->getEntityManager()->getClassMetadata(ComponentValue::class)->fieldMappings as $field) {
      $rsm->addFieldResult('acb_cv', "{$field['columnName']}", $field['fieldName']);
      $select[] = "acb_cv.{$field['columnName']}";
      if($field['columnName'] ==  "position")
      {
        $select[] = "acb_cv.{$field['columnName']} as acb_cv_position";
        $select2[] = "T01.{$field['columnName']} as T01_position";
      }
      $select2[] = "T01.{$field['columnName']}";
    }
    /*
     * Add Editor Component Type ID to ComponentValue
     */
    $select[] = "acb_cv.editor_component_type_id";
    $select2[] = "T01.editor_component_type_id";

    /*
     * Add Component ID to ComponentValue
     */
    $select[] = "acb_cv.component_id";
    $select2[] = "T01.component_id";


    /*
     * Add Add relation and columnName to ComponentValue
     */
    $rsm->addJoinedEntityResult(ComponentValues::class, "acb_cvs", "acb_cv", "children");
    $rsm->addFieldResult('acb_cvs', "acb_cvs_id", "id");
    $rsm->addFieldResult('acb_cvs', "acb_cvs_position", "position");

    $select[] = "acb_cvs.id as acb_cvs_id";
    $select2[] = "acb_cvs_1.id as acb_cvs_1_id";

    $select[] = "acb_cvs.position as acb_cvs_position";
    $select2[] = "acb_cvs_1.position as acb_cvs_1_position";

    /*
     * Add Add relation and columnName to ComponentValue
     */
    $rsm->addJoinedEntityResult(ComponentValue::class, "acb_cv_children", "acb_cvs", "children");
    foreach ($this->getEntityManager()->getClassMetadata(ComponentValue::class)->fieldMappings as $field) {
      $rsm->addFieldResult('acb_cv_children', "acb_cv_children_{$field['columnName']}", $field['fieldName']);
      $select[] = "acb_cv_children.{$field['columnName']} as acb_cv_children_{$field['columnName']} ";
      $select2[] = "acb_cv_children_1.{$field['columnName']} as acb_cv_children_1_{$field['columnName']}";
    }
    /*
     * Add Editor Component Type ID to ComponentValue
     */
    $select[] = "acb_cv_children.editor_component_type_id";
    $select2[] = "acb_cv_children_1.editor_component_type_id";


    /*
     * Add Add relation and columnName to EditorComponentType
     */
    $rsm->addJoinedEntityResult(EditorComponentType::class, "acb_ect_children", "acb_ec", "editorComponentTypes");
    $rsm->addJoinedEntityResult(EditorComponentType::class, "acb_ect", "acb_cv", "editorComponentType");
    $rsm->addJoinedEntityResult(EditorComponentType::class, "acb_ect_2", "acb_cv_children", "editorComponentType");
    foreach ($this->getEntityManager()->getClassMetadata(EditorComponentType::class)->fieldMappings as $field) {
      $rsm->addFieldResult('acb_ect_children', "acb_ect_children_{$field['columnName']}", $field['fieldName']);
      $rsm->addFieldResult('acb_ect', "acb_ect_{$field['columnName']}", $field['fieldName']);
      $rsm->addFieldResult('acb_ect_2', "acb_ect_2_{$field['columnName']}", $field['fieldName']);
      $selectMaster[] = "acb_ect_children.{$field['columnName']} as acb_ect_children_{$field['columnName']}";
      $select[] = "acb_ect.{$field['columnName']} as acb_ect_{$field['columnName']}";
      $select[] = "acb_ect_2.{$field['columnName']} as acb_ect_2_{$field['columnName']}";
      $select2[] = "acb_ect_1.{$field['columnName']} as acb_ect_1_{$field['columnName']}";
      $select2[] = "acb_ect_3.{$field['columnName']} as acb_ect_3_{$field['columnName']}";
    }
    /*
     * Add Editor Component Type ID to ComponentValue
     */
    $selectMaster[] = "acb_ect_children.editor_component_id";
    $select[] = "acb_ect.editor_component_id";
    $select[] = "acb_ect_2.editor_component_id";
    $select2[] = "acb_ect_1.editor_component_id";
    $select2[] = "acb_ect_3.editor_component_id";

    $query = $this->getEntityManager()->createNativeQuery('
WITH RECURSIVE T0 AS
  (SELECT '.implode(",", $select).'
    FROM austral_content_block_component_value AS acb_cv
    LEFT JOIN austral_content_block_component acb_c ON acb_c.id = acb_cv.component_id
    
    LEFT JOIN austral_content_block_editor_component_type acb_ect ON acb_ect.id = acb_cv.editor_component_type_id
    LEFT JOIN austral_content_block_editor_component acb_ec ON acb_ec.id = acb_ect.editor_component_id
    
    LEFT JOIN austral_content_block_component_values acb_cvs ON acb_cv.id = acb_cvs.parent_id
    LEFT JOIN austral_content_block_component_value acb_cv_children ON acb_cv_children.parent_id = acb_cvs.id
    LEFT JOIN austral_content_block_editor_component_type acb_ect_2 ON acb_ect_2.id = acb_cv_children.editor_component_type_id
    
    UNION ALL
    SELECT '.implode(",", $select2).'
    FROM T0
      JOIN austral_content_block_component_value AS T01 ON acb_cvs_id = T01.parent_id
      
      LEFT JOIN austral_content_block_editor_component_type acb_ect_1 ON acb_ect_1.id = T01.editor_component_type_id
      LEFT JOIN austral_content_block_editor_component acb_ec_1 ON acb_ec_1.id = acb_ect_1.editor_component_id
      
      LEFT JOIN austral_content_block_component_values acb_cvs_1 ON T01.id = acb_cvs_1.parent_id
      LEFT JOIN austral_content_block_component_value acb_cv_children_1 ON acb_cv_children_1.parent_id = acb_cvs_1.id
      LEFT JOIN austral_content_block_editor_component_type acb_ect_3 ON acb_ect_3.id = acb_cv_children_1.editor_component_type_id
 )
 
SELECT '.implode(",", $selectMaster).', T0.*
FROM austral_content_block_component acb_c
    LEFT JOIN T0 on T0.component_id = acb_c.id
    LEFT JOIN austral_content_block_editor_component acb_ec_master on acb_ec_master.id = acb_c.editor_component_id
    LEFT JOIN austral_content_block_editor_component_type acb_ect_children on acb_ect_children.editor_component_id = acb_ec_master.id
WHERE acb_c.object_id = :objectId  AND acb_c.object_classname = :objectClassname
ORDER BY acb_c.position ASC, T0.acb_cv_position ASC, T0.acb_cvs_position ASC, T0.acb_cv_children_position ASC, T0.acb_ect_position ASC;', $rsm);

    $query->setParameter("objectId", $objectId)
      ->setParameter("objectClassname", $classname);
    try {
      $objects = $query->execute();
    } catch (NoResultException $e) {
      $objects = array();
    }
    return $objects;

  }


}
