/**
 * Created by onysko on 03.11.2014.
 */

function initRelatedTable(table) {
    s('.delete_material', table).each(function(link) {
        link.ajaxClick(function(response) {
            s('.related_material_tab').html(response.table);
            initRelatedTable(table);
        })
    });
}

s('.related_material_table').pageInit(function(table) {
    initRelatedTable(table);
    s('.related_material_add').tinyboxAjax({
        html : 'popup',
        oneClickClose : true,
        renderedHandler : function(form, tb) {
            s('.add_related_form', form).ajaxSubmit(function(response) {
                s('.related_material_tab').html(response.table);
                initRelatedTable(table);
                tb._close();
            });
        }
    });
});