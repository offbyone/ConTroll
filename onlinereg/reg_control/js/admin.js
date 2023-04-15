current = null;
next = null;
mem = null;

function clearPermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=clear",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function updatePermissions(userid) {
    var formdata = $("#" + userid).serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=update",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}


function createAccount() {
    var formdata = $("#createUserForm").serialize();
    $('#test').append(formdata);
    $.ajax({
        url: 'scripts/permUpdate.php',
        method: 'POST',
        data: formdata+"&action=create",
        success: function (data, textStatus, jhXHR) {
            $('#test').append(JSON.stringify(data, null, 2));
            location.reload();
        }
    });
}

function settab(tabname) {
    switch (tabname) {
        case 'users-pane':
            if (current != null)
                current.close();
            if (next != null)
                next.close();
            if (mem != null)
                mem.close();
            break;

        case 'consetup-pane':            
            if (next != null)
                next.close();
            if (current != null)
                current.close();
            if (mem != null)
                mem.close();
            if (current == null)
                current = new consetup('current');
            current.open();
            break;

        case 'nextconsetup-pane':
            if (current != null)
                current.close();
            if (mem != null)
                mem.close();
            if (next != null)
                next.close();
            if (next == null)
                next = new consetup('next');
            next.open();
            break;
        case 'memconfig-pane':
            if (current != null)
                current.close();
            if (next != null)
                next.close();
            if (mem != null)
                mem.close();
            if (mem == null)
                mem = new memsetup();
            mem.open();
            break;
    }
}

function cellChanged(cell) {
    dirty = true;
    cell.getElement().style.backgroundColor = "#fff3cd";
}

function deleteicon(cell, formattParams, onRendered) {
    var value = cell.getValue();
    if (value == 0)
        return "&#x1F5D1;";
    return value;
}

function deleterow(e, row) {
    var count = row.getCell("uses").getValue();
    if (count == 0) {
        row.getCell("to_delete").setValue(1);
        row.getCell("uses").setValue('<span style="color:red;"><b>Del</b></span>');
    }
}
