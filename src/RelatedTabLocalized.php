<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 03.11.2014
 * Time: 13:35
 */

namespace samson\cms\web\relatedmaterial;

use \samson\core\SamsonLocale;


class RelatedTabLocalized extends FormTab
{
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

        // Add generic tab
        $this->tabs[] = new MaterialTab($form, $this, '');

        // Iterate available locales if fields exists
        if( sizeof($form->fields) && sizeof(SamsonLocale::$locales)) foreach (SamsonLocale::$locales as $locale)
        {
            // Create child tab
            $tab = new FieldTab($form, $this, $locale);

            // If it is not empty
            if($tab->filled()) {
                $this->tabs[] = $tab;
            }
        }
    }
}