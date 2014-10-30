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
	/** Tab name for showing in header */
	public $name = 'Подчиненные материалы';
	
	/** HTML identifier */
	public $id = 'related-tab';
	
	/** Tab sorting index */
	public $index = 5;

	/** Content view path */
	//private $content_view = 'tumbs/index';

	/** @see \samson\cms\web\material\FormTab::content() */
	public function content()
	{
		// Render content into inner content html
		if( isset($this->form->material) ) $this->content_html = m('related_material')->getRelatedTable( $this->form->material->id );

		// Render parent tab view
		return parent::content();
	}
}