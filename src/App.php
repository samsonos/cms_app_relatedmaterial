<?php
namespace samson\cms\web\relatedmaterial;
use samsonphp\event\Event;

/**
 * SamsonCMS application for interacting with material gallery
 * @author egorov@samsonos.com
 */
class App extends \samson\cms\App
{
	/** Application name */
	public $name = 'Подчиненные материалы';
	
	/** Hide application access from main menu */
	public $hide = true;
	
	/** Identifier */
	protected $id = 'related_material';

    protected $form;

    public function init(array $params = array())
    {
        Event::subscribe('samson.cms.input.change', array($this, 'saveFieldHandler'));
        return parent::init($params);
    }

	/** @see \samson\core\ExternalModule::init() */
	public function prepare( array $params = null )
	{
        // TODO: Change this logic to make tab loading more simple
		// Create new gallery tab object to load it 
		class_exists( ns_classname('RelatedTabLocalized','samson\cms\web\relatedmaterial') );
	}

    /**
     * @param $id int material parent id
     * @return array AJAX response
     */
    public function __async_addpopup($id) {
        return array('status' => 1, 'popup' => m('related_material')->view('popup/add_popup')->parentID($id)->output());
    }

    /**
     * Creating new related material
     * @return array AJAX response
     */
    public function __async_add() {
        $parent = dbQuery('\samson\cms\CMSMaterial')
                    ->id($_POST['parent_id'])
                    ->first();

        $fields_array_temp = array();
        $fields_array = array();
        foreach ($parent->cmsnavs() as $structure) {
            //Get all related fields
            if ($structure->type == 1) {
                $fields_array_temp = array_merge($fields_array_temp, $structure->fields());
            }
        }

        // Create comfy array performance
        foreach ($fields_array_temp as $field) {
            $fields_array[$field->id] = $field;
        }

        unset($fields_array_temp);
        // Get identifiers of founded fields
        $field_keys = array_keys($fields_array);

        $child = null;

        $parent->copy($child, $field_keys);

        $child->Name = 'related material';
        $child->Url = $_POST['Url'];
        $child->parent_id = $parent->id;
        $child->type = 2;
        $child->remains = 0;
        $child->save();

        return array('status' => 1);
    }

	/**
	 * Controller for deleting material image from gallery 
	 * @param string $id Gallery Image identifier
	 * @return array Async response array
	 */
	public function __async_delete($id)
	{
		// Async response
		$result = array( 'status' => false );

		if( dbQuery('material')->id($id)->first($material)) {
            if (dbQuery('material')->id($material->parent_id)->first($parent)) {
                $parent->remains = $parent->remains - $material->remains;
                $parent->save();
            }
            $material->delete();
			$result['status'] = true;
		}
		
		return $result;
	}

    /**
     * Async updating related table
     * @param $parentID
     * @return array
     */
	public function __async_table($parentID)
    {
        $form = new \samson\cms\web\material\Form($parentID);

        /** @var RelatedTabLocalized $tab */
        $tab = new RelatedTabLocalized($form);

        $content = $tab->getContent();

        return array('status' => 1, 'table' => $content);
    }

	public function getRelatedTable($material_id, $locale = '')
	{
        $parent = dbQuery('\samson\cms\CMSMaterial')->id($material_id)->first();

        $table = new RelatedTable($parent, $locale);

        $all = false;
        $multilingual = false;

        if (dbQuery('\samson\cms\CMSNavMaterial')->MaterialID($material_id)->join('structure')->cond('type', 1)->fields('StructureID', $strMats)) {
            // Check with locales we have in fields table
            if (dbQuery('structurefield')->join('field')->cond('field_local', 0)->cond('StructureID', $strMats)->first()) {
                $all = true;
            }
            if (dbQuery('structurefield')->join('field')->cond('field_local', 1)->cond('StructureID', $strMats)->first()) {
                $multilingual = true;
            }
        }

        if ($locale == '' && $all) {
            return m('related_material')->view('tab_view')->table($table->render())->currentID($material_id)->output();
        } elseif ($locale != '' && $multilingual) {
            return m('related_material')->view('tab_view')->table($table->render())->currentID($material_id)->output();
        }

        return '';
	}

    /**
     * External handler called on samson.cms.input.change event
     *
     * @param \samson\activerecord\dbRecord $dbObject Database entity instance
     * @param string $param Entity field
     * @param mixed $value Entity previous value
     */
    public function saveFieldHandler($dbObject, $param, $value)
    {
        var_dump($this);
        // If our object is material field
        if ($dbObject instanceof \samson\activerecord\materialfield) {
            // Get current material
            $material = dbQuery('material')->id($dbObject->MaterialID)->first();
            // If material can have related materials
            if ($material->type == 1 && $material->parent_id == 0) {
                // Get related materials identifiers
                $children = dbQuery('material')->cond('parent_id', $material->id)->fields('MaterialID');

                // For each child create or update material field record
                foreach ($children as $child) {
                    if (
                    !dbQuery('materialfield')
                        ->cond('FieldID', $dbObject->FieldID)
                        ->cond('locale', $dbObject->locale)
                        ->cond('MaterialID', $child)
                        ->first($childMaterialField)
                    ) {
                        $childMaterialField = new \samson\activerecord\materialfield(false);
                        $childMaterialField->MaterialID = $child;
                        $childMaterialField->FieldID = $dbObject->FieldID;
                        $childMaterialField->locale = $dbObject->locale;
                        $childMaterialField->Active = 1;
                    }
                    $childMaterialField->Value = $dbObject->Value;
                    if (isset($dbObject->numeric_value)) {
                        $childMaterialField->numeric_value = $dbObject->numeric_value;
                    }
                    $childMaterialField->save();
                }
            }
        }
        if ($dbObject instanceof \samson\activerecord\material && $param == 'remains') {
            /** @var \samson\activerecord\material $parent */
            $parent = null;
            if (dbQuery('material')->cond('MaterialID', $dbObject->parent_id)->first($parent)) {
                $parent->remains = (int)$parent->remains - (int)$value + (int)$dbObject->remains;
                $parent->save();
            }
        }
    }
}