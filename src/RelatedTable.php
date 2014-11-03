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

    /**
     * @var array $structures Collection of structures with related fields
     */
    private $structures = array();

    public function __construct(\samson\cms\CMSMaterial & $parent, $locale = 'ru')
    {
        $this->locale = $locale;

        // Save pointer to CMSMaterial
        $this->parent = & $parent;

        $fields_array = null;

        foreach ($parent->cmsnavs() as $structure) {
            if ($structure->type == 1) {
                $this->structures[$structure->id] = $structure;
                $fields_array = array_merge($this->fields, $structure->fields());
            }
        }

        foreach ($fields_array as $field) {
            $this->fields[$field->id] = $field;
        }

        unset($fields_array);
        $field_keys = array_keys($this->fields);

        trace($this->fields, true);

        // Prepare db query for all related material fields to structures
        $this->query = dbQuery( 'samson\cms\CMSMaterial')
            ->join('samson\cms\CMSMaterialField')
            ->cond('materialfield_FieldID', $field_keys)
            ->cond('locale', $this->locale)
            ->cond('material.parent_id', $this->parent->id)
            ->cond('material.Active', 1)
            ->cond('material.Draft', 0);

        $this->query->exec($data);

        trace($data, true);

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
                case '4': $input = Field::fromObject($matField, 'Value', 'Select')->optionsFromString($matField->Value); break;
                case '1': $input = Field::fromObject($matField, 'Value', 'File'); break;
                case '3': $input = Field::fromObject($matField, 'Value', 'Date'); break;
                case '7': $input = Field::fromObject($matField, 'numeric_value', 'Field'); break;
                default : $input = Field::fromObject($matField, 'Value', 'Field');
            }

            $tdHTML .= m()->view('table/row_td')->input($input)->output();
        }



        // Render field row
        return m()
            ->view($this->row_tmpl)
            ->td_view($tdHTML)
            ->pager($pager)
            ->output();
    }

} 