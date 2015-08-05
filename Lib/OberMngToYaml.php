<?php

namespace Arte\Ober2doctrineBundle\Lib;

class OberMngToYaml
{
    private $obermng;
    private $outputPath;
    public function __construct(OberMng $mng, $outputPath)
    {
        $this->obermng = $mng;
        $this->outputPath = $outputPath;
    }

    public function convertArray()
    {
        $ret = array();

//        echo "****convertArray()****\n";

        foreach($this->obermng->getEntitys() as $entity)
        {
//            echo "Entity execute\n";

            /* @var $entity \Arte\Ober2doctrineBundle\Entity\OberEntity */
            $id = $entity->getId();
            $entityName = $entity->getPhysicalName();
            $entityNameFullPath = $this->outputPath."\\".$entityName;

            $ret[$entityName] = array();

            $ret[$entityName][$entityNameFullPath] = array();
            $ymlEntity = $ret[$entityName][$entityNameFullPath];

            //type
            $ret[$entityName][$entityNameFullPath]["type"] = "entity";

            //indexes
            $ret[$entityName][$entityNameFullPath]["indexes"] = array();
            $indexes = $entity->getIndexes();
            foreach($indexes as $key => $value)
            {
//                echo "Index execute\n";
                /* @var $value \Arte\Ober2doctrineBundle\Entity\OberIndex */

                if($value->getType() != 3){
                    continue;
                }

                $indexPhysicalName = $value->getPhysicalName();
//                echo $indexPhysicalName."\n";
                $ret[$entityName][$entityNameFullPath]["indexes"][$indexPhysicalName] = array();
                $ret[$entityName][$entityNameFullPath]["indexes"][$indexPhysicalName]["columns"] = array();

                $indexColumns = $value->getColumns();
                foreach($indexColumns as $indexKey => $indexValue)
                {
//                    echo "***indexs***\n";
                    /* @var $indexValue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                    $ret[$entityName][$entityNameFullPath]["indexes"][$indexPhysicalName]["columns"][] = $indexValue->getPhysicalName();
                }
            }

            //unique
            $ret[$entityName][$entityNameFullPath]["uniqueConstraints"] = array();
            $indexes = $entity->getIndexes();
            foreach($indexes as $key => $value)
            {
//                echo "Index execute\n";
                /* @var $value \Arte\Ober2doctrineBundle\Entity\OberIndex */

                if($value->getType() != 2){
                    continue;
                }

                $indexPhysicalName = $value->getPhysicalName();
//                echo $indexPhysicalName."\n";
                $ret[$entityName][$entityNameFullPath]["uniqueConstraints"][$indexPhysicalName] = array();
                $ret[$entityName][$entityNameFullPath]["uniqueConstraints"][$indexPhysicalName]["columns"] = array();

                $indexColumns = $value->getColumns();
                foreach($indexColumns as $indexKey => $indexValue)
                {
//                    echo "***indexs***\n";
                    /* @var $indexValue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                    $ret[$entityName][$entityNameFullPath]["uniqueConstraints"][$indexPhysicalName]["columns"][] = $indexValue->getPhysicalName();
                }
            }

            //ID
            $ret[$entityName][$entityNameFullPath]["id"] = array();
            $attributes = $entity->getAttributes();
            foreach($attributes as $value)
            {
//                echo "ID execute\n";
                /* @var $value \Arte\Ober2doctrineBundle\Entity\OberAttribute */

                if(!$value->getPrimary()){
                    continue;
                }

                //type, length
                $idPhysicalName = $value->getPhysicalName();
                $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName] = array();
                $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName]["type"] = $value->getDataType();
                if($value->getLength() != null && $value->getLength() != "0"){
                    $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName]["length"] = (int)$value->getLength();
                }

                //auto increment
                if($value->getAutoIncrementFlug()){
                    $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName]["generator"] = array();
//                    $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName]["generator"]["strategy"] = "AUTO";
                    $ret[$entityName][$entityNameFullPath]["id"][$idPhysicalName]["generator"]["strategy"] = "IDENTITY";
                }
            }

            //fields
            $ret[$entityName][$entityNameFullPath]["fields"] = array();
            $attributes = $entity->getAttributes();
            foreach($attributes as $value)
            {
//                echo "Attribute execute\n";
                /* @var $value \Arte\Ober2doctrineBundle\Entity\OberAttribute */

                if($value->getPrimary()){
                    continue;
                }

                //type, length
                $attributePhysicalName = $value->getPhysicalName();
                $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName] = array();
                $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["type"] = $value->getDataType();
                if($value->getLength() != null && $value->getLength() != "0"){
                    if($value->getDataType() == "decimal"){
                        $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["precision"] = (int)$value->getLength();
                        $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["scale"] = (int)$value->getScale();
                    }else{
                        $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["length"] = (int)$value->getLength();
                    }
                }

                if($value->getNotNull()){
                    $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["nullable"] = false;
                }else{
                    $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["nullable"] = true;
                }

                //options
                if($value->getFixedFlag() || ($value->getDefault() !== "" && $value->getDefault() !== null)){
                    $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["options"] = array();

                    //fixed
                    if($value->getFixedFlag()){
                        $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["options"]["fixed"] = true;
                    }

                    //default
                    if(($value->getDefault() !== "" && $value->getDefault() !== null)){
                        $ret[$entityName][$entityNameFullPath]["fields"][$attributePhysicalName]["options"]["default"] = $value->getDefault();
                    }

                }

            }

            //relation OneToMany
            $ret[$entityName][$entityNameFullPath]["oneToMany"] = array();
            foreach($this->obermng->getRelations() as $relation)
            {
                /* @var $relation \Arte\Ober2doctrineBundle\Entity\OberRelation */

                //
                if($relation->getRelationType() == "ManyToMany"){
                    continue;
                }

                //parent
                /* @var $relationParentEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationParentEntity = $relation->getParentEntity();
                if($relationParentEntity->getId() != $id){
                    continue;
                }
                //child
                /* @var $relationChildEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationChildEntity = $relation->getChildEntity();
                $relationChildColumn = $relation->getChildColumn();
                if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                {
                    $childEntityName = $relation->getParentPropertyName();
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName] = array();
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName]["targetEntity"] = $relationChildEntity->getPhysicalName();
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName]["mappedBy"] = $relation->getChildPropertyName();
                }else{
                    $childEntityName = $relationChildEntity->getPhysicalName() . "s";
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()] = array();
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()]["targetEntity"] = $relationChildEntity->getPhysicalName();
                    $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()]["mappedBy"] = $entityName . $relationChildColumn->getPhysicalName();
                }

//                $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()] = array();
//                $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()]["targetEntity"] = $relationChildEntity->getPhysicalName();
//                $ret[$entityName][$entityNameFullPath]["oneToMany"][$childEntityName.$relationChildColumn->getPhysicalName()]["mappedBy"] = $entityName . $relationChildColumn->getPhysicalName();

//                //child
//                /* @var $relationChildEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
//                $relationChildEntity = $relation->getChildEntity();
//                $childEntityName = $relationChildEntity->getPhysicalName() . "s";
//
//                $ret[$entityName][$entityNameFullPath]["oneToMany"][strtolower($childEntityName)] = array();
//                $ret[$entityName][$entityNameFullPath]["oneToMany"][strtolower($childEntityName)]["targetEntity"] = $relationChildEntity->getPhysicalName();
//                $ret[$entityName][$entityNameFullPath]["oneToMany"][strtolower($childEntityName)]["mappedBy"] = strtolower($entityName);

            }

            //relation ManyToOne
            $ret[$entityName][$entityNameFullPath]["manyToOne"] = array();
            foreach($this->obermng->getRelations() as $relation)
            {
                //
                if($relation->getRelationType() == "ManyToMany"){
                    continue;
                }

                //child
                /* @var $relationChildEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationChildEntity = $relation->getChildEntity();
                if($relationChildEntity->getId() != $id){
                    continue;
                }

                //parent
                /* @var $relationParentEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationParentEntity = $relation->getParentEntity();
                $parentEntityName = $relationParentEntity->getPhysicalName();

                if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                {
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["targetEntity"] = $parentEntityName;
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["inversedBy"] = $relation->getParentPropertyName();

                    //joinColumns
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["joinColumns"] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["referencedColumnName"] = $relation->getParentColumn()->getPhysicalName();
                }else{
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["targetEntity"] = $parentEntityName;
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["inversedBy"] = $entityName . "s" . $relation->getChildColumn()->getPhysicalName();

                    //joinColumns
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["joinColumns"] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["referencedColumnName"] = $relation->getParentColumn()->getPhysicalName();
                }

                //fieldの場合は削除してnullableオプションを指定する
                if(array_key_exists($relation->getChildColumn()->getPhysicalName(), $ret[$entityName][$entityNameFullPath]["fields"]))
                {

                    if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                    {
                        $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["nullable"] = $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]["nullable"];
                    }else{
                        $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["referencedColumnName"] = $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]["nullable"];
                    }

//                    if(array_key_exists("nullable", $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]))
//                    {
//                        if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
//                        {
//                            $ret[$entityName][$entityNameFullPath]["manyToOne"][$relation->getChildPropertyName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["nullable"] = $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]["nullable"];
//                        }else{
//                            $ret[$entityName][$entityNameFullPath]["manyToOne"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["referencedColumnName"] = $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]["nullable"];
//                        }
//                    }
                    unset($ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]);
                }

                //idの場合は一度削除してassociationKey指定する
                if(array_key_exists($relation->getChildColumn()->getPhysicalName(), $ret[$entityName][$entityNameFullPath]["id"]))
                {
                    unset($ret[$entityName][$entityNameFullPath]["id"][$relation->getChildColumn()->getPhysicalName()]);

                    if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                    {
                        $ret[$entityName][$entityNameFullPath]["id"][$relation->getChildPropertyName()] = array();
                        $ret[$entityName][$entityNameFullPath]["id"][$relation->getChildPropertyName()]["associationKey"] = true;
                    }else{
                        $ret[$entityName][$entityNameFullPath]["id"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()] = array();
                        $ret[$entityName][$entityNameFullPath]["id"][$parentEntityName.$relation->getChildColumn()->getPhysicalName()]["associationKey"] = true;
                    }
                }

//                $ret[$entityName][$entityNameFullPath]["fields"][$relation->getChildColumn()->getPhysicalName()]["length"] = (int)$value->getLength();

//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)]["targetEntity"] = $parentEntityName;
//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)]["inversedBy"] = strtolower($entityName . "s");
//
//                //joinColumns
//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)]["joinColumns"] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)]["joinColumns"][$relation->getChildColumn()->getPhysicalName()] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToOne"][strtolower($parentEntityName)]["joinColumns"][$relation->getChildColumn()->getPhysicalName()]["referencedColumnName"] = $relation->getParentColumn()->getPhysicalName();

            }

            //relation ManyToMany
            $ret[$entityName][$entityNameFullPath]["manyToMany"] = array();

            //relation ManyToMany parent
            foreach($this->obermng->getRelations() as $relation)
            {
                /* @var $relation \Arte\Ober2doctrineBundle\Entity\OberRelation */

                //
                if($relation->getRelationType() != "ManyToMany"){
                    continue;
                }

                //parent
                /* @var $relationParentEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationParentEntity = $relation->getParentEntity();
                if($relationParentEntity->getId() != $id){
                    continue;
                }

                //child
                /* @var $relationChildEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationChildEntity = $relation->getChildEntity();
                $childEntityName = $relationChildEntity->getPhysicalName() . "s";

                if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                {
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["targetEntity"] = $relationChildEntity->getPhysicalName();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["inversedBy"] = $relation->getParentPropertyName();

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["name"] = strtolower($relation->getChildPropertyName()) . '_mid';

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"] = array();
                    foreach($relationParentEntity->getPrimaryAttributes() as $pavalue)
                    {
                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                        //strtolower($entityName . "s")
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()] = array();
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
                    }

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"] = array();
                    foreach($relationChildEntity->getPrimaryAttributes() as $pavalue)
                    {
                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()] = array();
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
                    }
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()] = array();
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["targetEntity"] = $relationChildEntity->getPhysicalName();
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["inversedBy"] = strtolower($entityName . "s");
//
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"] = array();
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["name"] = strtolower($relation->getChildPropertyName()) . '_mid';
//
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"] = array();
//                    foreach($relationParentEntity->getPrimaryAttributes() as $pavalue)
//                    {
//                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
//                        //strtolower($entityName . "s")
//                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()] = array();
//                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
//                    }
//
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"] = array();
//                    foreach($relationChildEntity->getPrimaryAttributes() as $pavalue)
//                    {
//                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
//                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()] = array();
//                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getChildPropertyName()]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
//                    }
//
                }else{
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["targetEntity"] = $relationChildEntity->getPhysicalName();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["inversedBy"] = $entityName . "s";

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["name"] = strtolower($entityName . "s") ."_". strtolower($childEntityName) . '_mid';

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["joinColumns"] = array();
                    foreach($relationParentEntity->getPrimaryAttributes() as $pavalue)
                    {
                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                        //strtolower($entityName . "s")
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()] = array();
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
                    }

                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["inverseJoinColumns"] = array();
                    foreach($relationChildEntity->getPrimaryAttributes() as $pavalue)
                    {
                        /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()] = array();
                        $ret[$entityName][$entityNameFullPath]["manyToMany"][$childEntityName]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
                    }
                }


//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["targetEntity"] = $relationChildEntity->getPhysicalName();
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["inversedBy"] = strtolower($entityName . "s");
//
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["name"] = strtolower($entityName . "s") ."_". strtolower($childEntityName) . '_mid';
//
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["joinColumns"] = array();
//                foreach($relationParentEntity->getPrimaryAttributes() as $pavalue)
//                {
//                    /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
//                    //strtolower($entityName . "s")
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()] = array();
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["joinColumns"][strtolower($entityName).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
////                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["joinColumns"][$pavalue->getPhysicalName()] = array();
////                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["joinColumns"][$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
//
//                }
//
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["inverseJoinColumns"] = array();
//                foreach($relationChildEntity->getPrimaryAttributes() as $pavalue)
//                {
//                    /* @var $pavalue \Arte\Ober2doctrineBundle\Entity\OberAttribute */
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()] = array();
//                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["inverseJoinColumns"][strtolower($relationChildEntity->getPhysicalName()).'_'.$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
////                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["inverseJoinColumns"][$pavalue->getPhysicalName()] = array();
////                    $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($childEntityName)]["joinTable"]["inverseJoinColumns"][$pavalue->getPhysicalName()]["referencedColumnName"] = $pavalue->getPhysicalName();
//                }
            }

            //relation ManyToMany child
            foreach($this->obermng->getRelations() as $relation)
            {
                /* @var $relation \Arte\Ober2doctrineBundle\Entity\OberRelation */

                //
                if($relation->getRelationType() != "ManyToMany"){
                    continue;
                }

                //child
                /* @var $relationChildEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationChildEntity = $relation->getChildEntity();
                if($relationChildEntity->getId() != $id){
                    continue;
                }

                //parent
                /* @var $relationParentEntity \Arte\Ober2doctrineBundle\Entity\OberEntity */
                $relationParentEntity = $relation->getParentEntity();
                $parentEntityName = $relationParentEntity->getPhysicalName();

                if($relation->getParentPropertyName() !== null && $relation->getParentPropertyName() !== null)
                {
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getParentPropertyName()] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getParentPropertyName()]["targetEntity"] = $parentEntityName;
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$relation->getParentPropertyName()]["mappedBy"] = $relation->getChildPropertyName();
                }else{
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"] = array();
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"]["targetEntity"] = $parentEntityName;
                    $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"]["mappedBy"] = $entityName . "s";
                }

//                $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"] = array();
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"]["targetEntity"] = $parentEntityName;
//                $ret[$entityName][$entityNameFullPath]["manyToMany"][$parentEntityName . "s"]["mappedBy"] = $entityName . "s";
////                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($parentEntityName . "s")] = array();
////                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($parentEntityName . "s")]["targetEntity"] = $parentEntityName;
////                $ret[$entityName][$entityNameFullPath]["manyToMany"][strtolower($parentEntityName . "s")]["mappedBy"] = strtolower($entityName . "s");
            }

            //commnet json
            if($entity->getCommentJson() !== null)
            {
                foreach($entity->getCommentJson() as $key => $value)
                {
                    $ret[$entityName][$entityNameFullPath][$key] = $value;
                }
            }

        }


        return $ret;
    }

}
