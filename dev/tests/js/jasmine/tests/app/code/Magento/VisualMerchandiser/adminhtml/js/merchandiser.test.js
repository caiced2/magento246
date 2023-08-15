define([
    'Magento_VisualMerchandiser/js/merchandiser',
    'jquery'
], function (Merchandiser, $) {
    'use strict';

    var model,
        switchSmartCategory,
        rulesSmartCategory,
        divSmartCategory,
        divRegularCategory,
        addNewRuleButton,
        documentBody;

    function createCategoryProductTable() {
        var tbl = document.createElement('table'),
            tblBody = document.createElement('tbody'),
            row = document.createElement('tr'),
            cell = document.createElement('td'),
            positionCell = document.createElement('div'),
            positionInput = document.createElement('input');

        positionCell.setAttribute('class', 'position');
        positionCell.appendChild(positionInput);
        cell.appendChild(positionCell);
        row.appendChild(cell);
        tblBody.appendChild(row);
        tbl.appendChild(tblBody);
        documentBody.appendChild(tbl);
        tblBody.setAttribute('class', 'ui-sortable');
    }

    beforeAll(function () {
        documentBody = document.getElementsByTagName('body')[0];

        switchSmartCategory = document.createElement('input');
        switchSmartCategory.setAttribute('id', 'catalog_category_smart_category_onoff');
        switchSmartCategory.setAttribute('type', 'checkbox');

        rulesSmartCategory = document.createElement('input');
        rulesSmartCategory.setAttribute('id', 'smart_category_rules');
        rulesSmartCategory.setAttribute('value', '');

        divSmartCategory = document.createElement('div');
        divSmartCategory.setAttribute('id', 'manage-rules-panel');

        divRegularCategory = document.createElement('div');
        divRegularCategory.setAttribute('id', 'regular-category-settings');

        addNewRuleButton = document.createElement('button');
        addNewRuleButton.setAttribute('id', 'add_new_rule_button');

        documentBody.appendChild(switchSmartCategory);
        documentBody.appendChild(rulesSmartCategory);
        documentBody.appendChild(divSmartCategory);
        documentBody.appendChild(divRegularCategory);
        documentBody.appendChild(addNewRuleButton);

        createCategoryProductTable();

        model = new Merchandiser();
        model.element = $('body');
        model.setupSmartCategory();
    });

    describe('Magento_VisualMerchandiser/js/merchandiser', function () {
        it('test enable smart category', function () {
            $(switchSmartCategory).trigger('click');
            expect($(switchSmartCategory).is(':checked')).toBeTruthy();
            expect($(rulesSmartCategory).is(':disabled')).toBeFalsy();
            expect($(divSmartCategory).hasClass('hidden')).toBeFalsy();
            expect($(divRegularCategory).hasClass('hidden')).toBeTruthy();
            expect($('.position input').is(':disabled')).toBeTruthy();
        });

        it('test disable smart category', function () {
            $(switchSmartCategory).trigger('click');
            expect($(switchSmartCategory).is(':checked')).toBeFalsy();
            expect($(divSmartCategory).hasClass('hidden')).toBeTruthy();
            expect($(divRegularCategory).hasClass('hidden')).toBeFalsy();
            expect($('.position input').is(':disabled')).toBeFalsy();
        });
    });
});
