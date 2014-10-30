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

	/** @see \samson\core\ExternalModule::init() */
	public function prepare( array $params = null )
	{
        // TODO: Change this logic to make tab loading more simple
		// Create new gallery tab object to load it 
		class_exists( ns_classname('MaterialTab','samson\cms\web\relatedmaterial') );
	}

    public function __async_add($id) {
        $parent = dbQuery('material')->id($id)->first();
        $material = new \samson\activerecord\material(false);
        $material->Name = $parent->Name;
        $material->Url = $parent->Url.'-'.generate_password(5);
        $material->parent_id = $id;
        $material->type = 1;
        $material->Active = 1;
        $material->Published = 1;
        $material->save();

        $this->cloneParent($parent, $material);
    }

    public function cloneParent($parent, $child = null)
    {
        $materialfields = dbQuery('materialfield')
                        ->cond('MaterialID', $parent->id)
                        ->join('material')
                        ->join('structure')
                        ->cond('structure.type', 1)
                        ->exec($materialfields);

        //trace($materialfields);
    }
	/**
	 * Controller for deleting material image from gallery 
	 * @param string $id Gallery Image identifier
	 * @return array Async response array
	 */
	public function __async_delete( $id )
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
	 * Controller for rendering gallery images list
	 * @param string $material_id Material identifier 
	 * @return array Async response array
	 */
	public function __async_update( $material_id )
	{				
		return array('status' => true, 'html' => $this->getRelatedTable($material_id));
	}

	public function getRelatedTable($material_id)
	{
		// Get all material images
		$items_html = '';

        $materials = null;

        if (dbQuery('\samson\cms\Material')->parent_id($material_id)->exec($materials)) {
            foreach ($materials as $material) {

            }
        }

        $parent = dbQuery('material')->id($material_id)->first();

        //$this->cloneParent($parent);

        return '1';

	}
}