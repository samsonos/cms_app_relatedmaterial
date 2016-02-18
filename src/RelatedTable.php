<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 31.10.2014
 * Time: 16:15
 */

namespace samson\cms\web\relatedmaterial;

use samson\activerecord\dbQuery;
use samson\pager\Pager;

class RelatedTable extends \samson\cms\table\Table
{
    /** Change table template */
    public $table_tmpl = 'table/index';

    public $dbQuery;

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
        $this->dbQuery = new dbQuery();
        // Retrieve pointer to current module for rendering
        $this->renderModule = & s()->module($this->renderModule);

        $this->locale = $locale;

        // Save pointer to CMSMaterial
        $this->parent = & $parent;

        // Get all child materials identifiers from parent
        $materialIDs = dbQuery('material')
            ->cond('material.parent_id', $this->parent->id)
            ->cond('material.Active', 1)
            ->cond('material.Draft', 0)
            ->fields('MaterialID');
        
        // Iterate all parent material structures
        foreach ($parent->cmsnavs() as $structure) {
            // Use only special structures
            if (isset($structure->type) && $structure->type == 1) {
                //$this->structures[$structure->id] = $structure;
                // Iterate all fields
                foreach ($structure->fields() as $field) {
                    // Use only localizable fields
                    if ($locale != '' && $field->local == 1 && !isset($this->fields[$field->id])) {
                        // Gather key => value fields collection
                        $this->fields[$field->id] = $field;

                        // Iterate all child materials
                        foreach ($materialIDs as $materialID) {
                            // Check if they already have this material field
                            if (!dbQuery('materialfield')->MaterialID($materialID)->locale($this->locale)->FieldID($field->id)->first()) {
                                // Create material field record
                                $mf = new \samson\activerecord\materialfield(false);
                                $mf->MaterialID = $materialID;
                                $mf->FieldID = $field->id;
                                $mf->Active = 1;
                                $mf->locale = $this->locale;
                                $mf->save();
                            }
                        }
                    } else if ($locale == '' && $field->local == 0 && !isset($this->fields[$field->id])) {
                        // Gather key => value fields collection
                        $this->fields[$field->id] = $field;

                        // Iterate all child materials
                        foreach ($materialIDs as $materialID) {
                            // Check if they already have this material field
                            if (!dbQuery('materialfield')->MaterialID($materialID)->locale($this->locale)->FieldID($field->id)->first()) {
                                // Create material field record
                                $mf = new \samson\activerecord\materialfield(false);
                                $mf->MaterialID = $materialID;
                                $mf->FieldID = $field->id;
                                $mf->Active = 1;
                                $mf->save();
                            }
                        }
                    }
                }
            }
        }

        // Get all child materials material fields for this locale
        $this->query = dbQuery('material')
            ->parent_id($this->parent->id)
            ->join('materialfield')
            ->cond('materialfield_FieldID', array_keys($this->fields))
            ->cond('materialfield_locale', $this->locale)
        ;

        // Constructor treed
        parent::__construct( $this->query );
    }


    public function row(& $material, Pager & $pager = null, $module = null)
    {
        $input = null;
        $tdHTML = '';
        foreach ($this->fields as $field) {
            foreach ($material->onetomany['_materialfield'] as $mf) {
                if ($mf->FieldID == $field->FieldID && $mf->locale == $this->locale) {
                    // Create fields
                    if ($field->Type < 9) {
                        $input = m('samsoncms_input_application')
                            ->createFieldByType($this->dbQuery, $field->Type, $mf);
                    }
                    if ($field->Type == 4) {
                        /** @var \samsoncms\input\select\Select $input Select input type */
                        $input->build();
                    }

                    $tdHTML .= $this->renderModule->view('table/tdView')->input($input)->output();
                    break;
                }
            }
        }
        $input = m('samsoncms_input_number_application')->createField($this->dbQuery, $material, 'remains');
        $tdHTML .= $this->renderModule->view('table/tdView')->input($input)->output();

        // Render field row
        return $this->renderModule
            ->view($this->row_tmpl)
            ->materialName(m('samsoncms_input_text_application')->createField($this->dbQuery, $material, 'Url'))
            ->materialID($material->id)
            ->parentID($material->parent_id)
            ->td_view($tdHTML)
            ->pager($pager)
            ->output();
    }

    public function render( array $db_rows = null, $module = null)
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
            $thHTML .= $this->renderModule->view('table/thView')->set($field->Name, 'fieldName')->output();
        }
        $thHTML .= $this->renderModule->view('table/thView')->set('Остаток', 'fieldName')->output();

        if ($rows != '') {
            // Render table view
            return $this->renderModule
                ->view($this->table_tmpl)
                ->set($thHTML, 'thView')
                ->set($this->pager)
                ->set($rows, 'rows')
                ->output();
        } else {
            return '';
        }

    }

} 
