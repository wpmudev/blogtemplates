jQuery(document).ready(function() {
    jQuery('.form-table:last tr:last').before('\
    <tr class="form-field form-required">\
        <th scope="row">' + nbt.selector_title + '</th>\
        <td>' + nbt.dropdown + '</td>\
    </tr>');
});