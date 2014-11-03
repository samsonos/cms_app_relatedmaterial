<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 31.10.2014
 * Time: 16:15
 */

namespace samson\cms\web\relatedmaterial;

use samson\pager\Pager;

class RelatedTable extends \samson\cms\table\Table
{
    /** Change table template */
    public $table_tmpl = 'table/index';

    /** Default table row template */
    public $row_tmpl = 'table/row';

    /** Existing CMSMaterial field records */
    private $fields = array();

    /** Pointer to CMSMaterial */
    private $parent;

    /** Fields locale */
    private $locale;

    /** @var string  */
    protected $renderModule = 'related_material';

    /**
     * @var array $structures Collection of structures with related fields
     */
    private $structures = array();

    public function __construct(\samson\cms\CMSMaterial & $parent, $locale = 'ru')
    {
        // Retrieve pointer to current module for rendering
        $this->renderModule = & s()->module($this->renderModule);

        $this->locale = $locale;

        // Save pointer to CMSMaterial
        $this->parent = & $parent;

        $fields_array = array();

        foreach ($parent->cmsnavs() as $structure) {
            if ($structure->type == 1) {
                $this->structures[$structure->id] = $structure;
                $fields_array = array_merge($fields_array, $structure->fields());
            }
        }

        foreach ($fields_array as $field) {
            $this->fields[$field->id] = $field;
        }

        unset($fields_array);
        $field_keys = array_keys($this->fields);

        // Prepare db query for all related material fields to structures
        $this->query = dbQuery( 'samson\cms\CMSMaterial')
            ->join('samson\cms\CMSMaterialField')
            ->cond('materialfield_FieldID', $field_keys)
            ->cond('locale', $this->locale)
            ->cond('material.parent_id', $this->parent->id)
            ->cond('material.Active', 1)
            ->cond('material.Draft', 0);

        // Constructor treed
        parent::__construct( $this->query );
    }


    public function row( & $db_row, Pager & $pager = null )
    {

        // Get materialfields metadata
        $materialFields = & $db_row->onetomany['_materialfield'];

        // If parent Field object not found countinue
        if( !isset( $materialFields ) ) return e('materialField# ## - Field not found(##)', E_SAMSON_CMS_ERROR, array( $db_row->id, $db_row->FieldID));

        $tdHTML = '';
        foreach ($materialFields as $matField) {
            $field = $this->fields[$matField->FieldID];

            // Depending on field type
            switch ($field->Type)
            {
                case '4': $input = \samson\cms\input\Field::fromObject($matField, 'Value', 'Select')->optionsFromString($matField->Value); break;
                case '1': $input = \samson\cms\input\Field::fromObject($matField, 'Value', 'File'); break;
                case '3': $input = \samson\cms\input\Field::fromObject($matField, 'Value', 'Date'); break;
                case '7': $input = \samson\cms\input\Field::fromObject($matField, 'numeric_value', 'Field'); break;
                default : $input = \samson\cms\input\Field::fromObject($matField, 'Value', 'Field');
            }

            $tdHTML .= $this->renderModule->view('table/tdView')->input($input)->output();
        }

        // Render field row
        return $this->renderModule
            ->view($this->row_tmpl)
            ->materialName(\samson\cms\input\Field::fromObject($db_row, 'Url', 'Field'))
            ->materialID($db_row->id)
            ->parentID($db_row->parent_id)
            ->td_view($tdHTML)
            ->pager($pager)
            ->output();
    }

    public function render( array $db_rows = null)
    {
        // Rows HTML
        $rows = '';

        // if no rows data is passed - perform db request
        if( !isset($db_rows) )	$db_rows = $this->query->exec();

        // If we have table rows data
        if( is_array($db_rows ) )
        {
            // Save quantity of rendering rows
            $this->last_render_count = sizeof($db_rows);

            // Iterate db data and perform rendering
            foreach( $db_rows as & $db_row )
            {
                $rows .= $this->row( $db_row, $this->pager );
            }
        }
        // No data found after query, external render specified
        else $rows .= $this->emptyrow($this->query, $this->pager );

        //elapsed('render pages: '.$this->pager->total);

        $thHTML = '';

        foreach ($this->fields as $field) {
            $thHTML .= $this->renderModule->view('table/thView')->fieldName($field->Name)->output();
        }

        // Render table view
        return $this->renderModule
            ->view($this->table_tmpl)
            ->thView($thHTML)
            ->set( $this->pager )
            ->rows($rows)
            ->output();
    }

} 