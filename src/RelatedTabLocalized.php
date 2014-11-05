<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 03.11.2014
 * Time: 13:35
 */

namespace samson\cms\web\relatedmaterial;

use \samson\core\SamsonLocale;
use \samson\cms\web\material\FormTab;

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
    public function __construct(\samson\cms\web\material\Form & $form, FormTab & $parent = null)
    {
        // Call parent constructor
        parent::__construct( $form, $parent );

        $allTab = new MaterialTab($form, $this, '');
        // Add generic tab
        if ($allTab->filled()) {
            $this->tabs[] = $allTab;
        }

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

    public function getContent()
    {
        $content = '';

        // Iterate tab group tabs
        foreach ( $this->tabs as $tab )
        {
            // If tab inner html is not empty
            if( isset($tab->content_html{0}))
            {
                // Render top tab content view
                $content .= m('related_material')->view('content')->tab($tab)->output();
            }
        }

        return $content;
    }
}