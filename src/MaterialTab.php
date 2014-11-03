<?php
namespace samson\cms\web\relatedmaterial;

use samson\cms\web\material\FormTab;

/**
 * Gallery Tab for CMSMaterial form 
 *
 * @author Egorov Vitaly <egorov@samsonos.com>
 */
class MaterialTab extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = false;
	
	/** Tab sorting index */
	public $index = 2;

	/** Content view path */
	//private $content_view = 'tumbs/index';

	/** @see \samson\cms\web\material\FormTab::content() */
	/*public function content()
	{
		// Render content into inner content html
		if( isset($this->form->material) ) $this->content_html = m('related_material')->getRelatedTable( $this->form->material->id );

		// Render parent tab view
		return parent::content();
	}*/

    /**
     * Constructor
     * @param Form $form Pointer to form
     */
    public function __construct( \samson\cms\web\material\Form & $form, FormTab & $parent = null, $locale = null )
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        // Save tab header name as locale name
        $this->name = $locale == '' ? t('все', true) : $locale;

        // Add locale to identifier
        $this->id = $parent->id.($locale == '' ? '' : '-'.$locale);

        // Set pointer to CMSMaterial
        $material = & $form->material;

        $this->content_html = m('related_material')->getRelatedTable($material->id, $locale);
    }

    /**
     * Check if tab has content
     * @return bool True if tab has rendered content
     */
    public function filled()
    {
        return strlen($this->content_html) > 0;
    }
}