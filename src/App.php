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

    public function __async_addpopup($id) {
        return array('status' => 1, 'popup' => m('related_material')->view('popup/add_popup')->parentID($id)->output());
    }

    public function __async_add() {
        $parent = dbQuery('samson\cms\CMSMaterial')
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

        $this->cloneParent($parent, $material);

        return array('status' => 1);
    }

    public function cloneParent($parent, $child = null)
    {
        $fields_array_temp = array();
        $fields_array = array();
        foreach ($parent->cmsnavs() as $structure) {
            if ($structure->type == 1) {
                $fields_array_temp = array_merge($fields_array_temp, $structure->fields());
            }
        }

        foreach ($fields_array_temp as $field) {
            $fields_array[$field->id] = $field;
        }

        unset($fields_array_temp);
        $field_keys = array_keys($fields_array);


        $parent = dbQuery ('samson\cms\CMSMaterial')
            ->id($parent->id)
            ->join('samson\cms\CMSMaterialField')
            ->join('samson\cms\CMSGallery')
            ->join('samson\cms\CMSNavMaterial')
            ->first();


        foreach ($parent->onetomany['_structurematerial'] as $cmsnav) {
            $structurematerial = new \samson\activerecord\structurematerial(false);
            $structurematerial->MaterialID = $child->id;
            $structurematerial->StructureID = $cmsnav->StructureID;
            $structurematerial->Active = 1;
            $structurematerial->save();
        }

        foreach ($parent->onetomany['_materialfield'] as $matfield) {
            $materialfield = new \samson\activerecord\materialfield(false);
            $materialfield->MaterialID = $child->id;
            $materialfield->FieldID = $matfield->FieldID;
            if (in_array($materialfield->FieldID, $field_keys)) {
                $materialfield->Value = '';
            } else {
                $materialfield->Value = $matfield->Value;
            }
            $materialfield->numeric_value = $matfield->numeric_value;
            $materialfield->locale = $matfield->locale;
            $materialfield->Active = $matfield->Active;
            $materialfield->save();
        }

        foreach ($parent->onetomany['_gallery'] as $cmsgallery) {
            $gallery = new \samson\activerecord\gallery(false);
            $gallery->MaterialID = $child->id;
            $gallery->Path = $cmsgallery->Path;
            $gallery->Src = $cmsgallery->Src;
            $gallery->Name = $cmsgallery->Name;
            $gallery->Description = $cmsgallery->Description;
            $gallery->Active = $cmsgallery->Active;
            $gallery->save();
        }

        //trace($materialfields);
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
	
	public function __async_table($parentID)
    {
        $form = new \samson\cms\web\material\Form($parentID);

        /** @var RelatedTabLocalized $tab */
        $tab = new RelatedTabLocalized($form);

        $content = $tab->getContent();

        //trace($this->form);
        //trace($content);

        return array('status' => 1, 'table' => $content);
    }

	public function getRelatedTable($material_id, $locale = '')
	{
        $parent = dbQuery('\samson\cms\CMSMaterial')->id($material_id)->first();

        $table = new RelatedTable($parent, $locale);

        $all = false;
        $multilingual = false;
        if (dbQuery('\samson\cms\CMSNavMaterial')->MaterialID($material_id)->join('structure')->cond('type', 1)->fields('StructureID', $strMats)) {
            /*foreach ($strMats as $strMat) {
                $structure = $strMat->onetoone['_structure'];

            }*/
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