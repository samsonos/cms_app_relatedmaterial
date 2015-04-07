<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 03.11.2014
 * Time: 13:35
 */

namespace samson\cms\web\relatedmaterial;

use samson\core\SamsonLocale;
use samsoncms\app\material\FormTab;
use samsoncms\app\material\Form;

class RelatedTabLocalized extends FormTab
{
    /** Meta static variable to disable default form rendering */
    public static $AUTO_RENDER = true;

    /** Tab name for showing in header */
    public $name = 'Подчиненные материалы';

    /** HTML identifier */
    public $id = 'related-tab';

    /** Tab sorting index */
    public $index = 5;

    /**
     * Constructor
     * @param Form $form Pointer to form
     */
    public function __construct(Form & $form, FormTab & $parent = null)
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        // If material type is related than show this tab
        if ($form->material->type == 1) {
            // Create all locale sub tab
            $allTab = new MaterialTab($form, $this, '');

            $this->tabs[] = $allTab;

            // Iterate available locales if fields exists
            if (sizeof(SamsonLocale::$locales)) {
                foreach (SamsonLocale::$locales as $locale) {
                    // Create child tab
                    $tab = new MaterialTab($form, $this, $locale);

                    // If it is not empty
                    if ($tab->filled()) {
                        $this->tabs[] = $tab;
                    }
                }
            }
        }
    }

    public function getContent()
    {
        $content = '';

        // Iterate tab group tabs
        foreach ($this->tabs as $tab) {
            // If tab inner html is not empty
            if (isset($tab->content_html{0})) {
                // Render top tab content view
                $content .= m('related_material')->view('content')->tab($tab)->output();
            }
        }

        return $content;
    }
}