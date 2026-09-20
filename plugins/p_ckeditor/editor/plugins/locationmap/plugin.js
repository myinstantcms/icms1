/*
 * locationmap — вставка карты с отмеченной точкой (плагин InstantCMS).
 * Само диалоговое окно лежит в dialogs/locationmap.js и определяет
 * диалог 'locationMapDialog'.
 */
CKEDITOR.plugins.add('locationmap', {
    requires: 'dialog',
    init: function (editor) {
        CKEDITOR.dialog.add('locationMapDialog', this.path + 'dialogs/locationmap.js');
        editor.addCommand('locationMap', new CKEDITOR.dialogCommand('locationMapDialog'));
        editor.ui.addButton('LocationMap', {
            label: 'Карта с точкой',
            command: 'locationMap',
            toolbar: 'insert'
        });
    }
});
