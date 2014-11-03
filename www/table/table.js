/**
 * Created by onysko on 03.11.2014.
 */

function initRelatedTable(table) {
    s('.delete_material', table).each(function(link) {
        link.ajaxClick(function(response) {
            s('#related-tab-tab').html(response.table);
            SamsonCMS_InputField(s('.__inputfield.__textarea'));
            s('.__inputfield.__textarea').pageInit( SamsonCMS_InputField );
        })
    });
}

s(document).pageInit(function(table) {
    s('.related_material_table').each(function(table) {
        initRelatedTable(table);
    });

    s('.related_material_add').each(function(link) {
        link.tinyboxAjax({
            html : 'popup',
            oneClickClose : true,
            renderedHandler : function(form, tb) {
                s('.add_related_form', form).ajaxSubmit(function(response) {
                    s('#related-tab-tab').html(response.table);
                    SamsonCMS_InputField(s('.__inputfield.__textarea'));
                    initRelatedTable(table);
                    tb._close();
                });
            }
        });
    });
});