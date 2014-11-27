<?php
namespace samson\cms\web\relatedmaterial;

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
        $material = new \samson\activerecord\material(false);
        $material->Name = 'related material';
        $material->Url = $_POST['Url'];
        $material->parent_id = $parent->id;
        $material->type = 2;
        $material->Active = 1;
        $material->Published = 1;
        $material->save();

        // Clone parent relations for new material
        $this->cloneParent($parent, $material);

        return array('status' => 1);
    }

    /**
     * Cloning relations
     * @param $parent \samson\cms\CMSMaterial
     * @param null $child \samson\activerecord\material
     */
    public function cloneParent($parent, $child = null)
    {
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

        $parent->copy($child, $field_keys);
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
}