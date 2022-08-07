$(document).on('ready', function () {
    hideBlock('#conflictView');
    hideBlock('#editPerson');
    hideBlock('#searchPerson');
    hideBlock('#addPerson');
});

function conflictGetPerid() {
    var perid=prompt("Enter Perid");
    $.ajax({
        url: 'scripts/editPerson.php',
        method: "GET",
        data: {'id' : perid},
        success: function (data, textStatus, jqXHR) {
            if(data['error'] != undefined) { console.log(data['error']); }
            data['full_name'] = data['first_name'] + " " + data['last_name'];
            console.log(data);
            loadOldPerson(data);
        }
    });
}

function fetchNewPerson(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/getNewPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXHR) {
            if(data['error'] != undefined) { console.log(data['error']); }
            //$('#test').empty().append(JSON.stringify(data, null, 2));
            loadNewPerson(data);
            displaySearchResults(data, loadOldPerson);
        }
    });
}

function fetchPerson(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/editPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            //$('#test').empty().append(JSON.stringify(data, null, 2));
            showEditPerson(data);
        }
    });
}

function checkPerson(form) {
    var postData = $(form).serialize();
    $.ajax({
        url: 'scripts/addPerson.php',
        method: "POST",
        data: postData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            //$('#test').empty().append(JSON.stringify(data, null, 2));

            getData = "id="+data['id'];
            $.ajax({
                url: 'scripts/getNewPerson.php',
                method: "GET",
                data: getData,
                success: function (data, textStatus, jqXHR) {
                    if(data['error'] != undefined) { console.log(data['error']); }
                    //$('#test').empty().append(JSON.stringify(data, null, 2));
                    loadNewPerson(data);
                    displaySearchResults(data, loadOldPerson);
                }
            });
        }
    });
}

function getPerson(obj) {
    var getData = "id="+obj.id;
    $.ajax({
        url: 'scripts/editPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            //$('#test').empty().append(JSON.stringify(data, null, 2));
            showEditPerson(data);
        }
    });
}

function getUpdated(data, textStatus, jqXhr) {
    getPerson(data['post']);
}

function findPerson(form) {
    var getData = $(form).serialize();
    $.ajax({
        url: 'scripts/findPerson.php',
        method: "GET",
        data: getData,
        success: function (data, textStatus, jqXhr) {
            if(data['error'] != undefined) { console.log(data['error']); }
            displaySearchResults(data, getPerson)
        }
    });
}

function loadNewPerson(data) {
    var user = data["new"];
    if (user != null) {
        $('#conflictNewIDfield').val(data["new"]['id']);
        $('#conflictViewForm').data('User', user);
        $('#conflictFormOldID').empty();
        $('#conflictFormNewID').empty().append(user['id']);
        $('#conflictFormNewID').empty().append(user['perid']);
        if (user['perid']) {
            $("#conflictViewForm :input[type=submit]").prop('disabled', true).addClass('disabled');
        } else {
            $("#conflictViewForm :input[type=submit]").prop('disabled', false).removeClass('disabled');
        }

        $('#conflictFormDbName').empty();
        $('#conflictFormUserName').empty().append(user['full_name']);
        $('#conflictFormNewFName').val(user['first_name']);
        $('#conflictFormNewMName').val(user['middle_name']);
        $('#conflictFormNewLName').val(user['last_name']);
        $('#conflictFormNewSuffix').val(user['suffix']);

        $('#conflictFormDbBadge').empty();
        $('#conflictFormNewBadge').val(user['badge_name']);
        $('#conflictFormUserBadge').empty().append(user['badge_name']);

        $('#conflictFormDbAddr').empty();
        $('#conflictFormNewAddr').val(user['address']);
        $('#conflictFormUserAddr').empty().append(user['address']);

        $('#conflictFormDbAddr2').empty();
        $('#conflictFormNewAddr2').val(user['addr_2']);
        $('#conflictFormUserAddr2').empty().append(user['addr_2']);

        $('#conflictFormDbLocale').empty();
        $('#conflictFormNewCity').val(user['city']);
        $('#conflictFormNewState').val(user['state']);
        $('#conflictFormNewZip').val(user['zip']);
        $('#conflictFormNewCountry').val(user['country']);
        $('#conflictFormUserLocale').empty().append(user['locale']);

        $('#conflictFormDbEmail').empty();
        $('#conflictFormNewEmail').val(user['email_addr']);
        $('#conflictFormUserEmail').empty().append(user['email_addr']);

        $('#conflictFormDbPhone').empty();
        $('#conflictFormNewPhone').val(user['phone']);
        $('#conflictFormUserPhone').empty().append(user['phone']);

        $('#conflictUpdate').attr('disabled', true);
        showBlock('#conflictView');
    } else {
        hideBlock('#conflictView')
    }
}

function loadOldPerson(objData) {
    //$('#test').empty().append(JSON.stringify(data, null, 2));
    $('#conflictViewForm').data('Db', objData);
    $('#conflictFormOldID').empty().append(objData['id']);
    $('#conflictOldIDfield').val(objData['id']);
    if(objData['banned']== 'Y') {
      $('#conflictFormOldID').append("(banned)");
    } else if(objData['active'] == 'N') {
      $('#conflictFormOldID').append("(inactive)");
    }

    $('#conflictFormDbName').empty().append(objData['full_name']);
    $('#conflictFormDbBadge').empty().append(objData['badge_name']);
    $('#conflictFormDbAddr').empty().append(objData['address']);
    $('#conflictFormDbAddr2').empty().append(objData['addr_2']);
    $('#conflictFormDbLocale').empty().append(objData['locale']);
    $('#conflictFormDbCouuntry').empty().append(objData['country']);
    $('#conflictFormDbEmail').empty().append(objData['email_addr']);
    $('#conflictFormDbPhone').empty().append(objData['phone']);

    $('#conflictUpdate').attr('disabled', false);

}

function setField(field, source) {
    if(field == 'all' || field == 'Name') {
        $('#conflictFormNewFName').val($('#conflictViewForm').data(source)['first_name']);
        $('#conflictFormNewMName').val($('#conflictViewForm').data(source)['middle_name']);
        $('#conflictFormNewLName').val($('#conflictViewForm').data(source)['last_name']);
        $('#conflictFormNewSuffix').val($('#conflictViewForm').data(source)['suffix']);
    }
    if(field == 'all' || field == 'Badge') {
        $('#conflictFormNewBadge').val($('#conflictViewForm').data(source)['badge_name']);
    }
    if(field == 'all' || field == 'Addr') {
        $('#conflictFormNewAddr').val($('#conflictViewForm').data(source)['address']);
        $('#conflictFormNewAddr2').val($('#conflictViewForm').data(source)['addr_2']);
        $('#conflictFormNewCity').val($('#conflictViewForm').data(source)['city']);
        $('#conflictFormNewState').val($('#conflictViewForm').data(source)['state']);
        $('#conflictFormNewZip').val($('#conflictViewForm').data(source)['zip']);
        $('#conflictFormNewCountry').val($('#conflictViewForm').data(source)['country']);
    }
    if(field == 'all' || field == 'Email') {
        $('#conflictFormNewEmail').val($('#conflictViewForm').data(source)['email_addr']);
    }
    if(field == 'all' || field == 'Phone') {
        $('#conflictFormNewPhone').val($('#conflictViewForm').data(source)['phone']);
    }
}

function resolveConflict(data, textStatus, jqXhr) {
    //$('#test').empty().append(JSON.stringify(data, null, 2)); 
    if(data['error'] != null) { 
        $('#test').empty().append(JSON.stringify(data, null, 2)); 
    }
    updateConflictCount();
    fetchNewPerson('#fetchNewPerson') 
}

function updateConflict(source) {
    switch(source) {
        case "new":
            submitForm("#conflictViewForm",
                "scripts/addPersonFromConflict.php",
                resolveConflict, null);
            break;
        case "existing":
            submitForm('#conflictViewForm',
                "scripts/editPersonFromConflict.php",
                resolveConflict, null);

            break;
        default:
            alert("How are you dealing with this conflict?");
    }
}

function updateConflictCount() {
    $.ajax({
        url: 'scripts/countConflict.php',
        method: "GET",
        success: function (data, textStatus, jqXhr) {
            $('#conflictCount').empty().append(data['count']);
        }
    });
}

function showEditPerson(data) {
    var formObj = "#editPersonForm :input[name='";

    $("#editPersonForm").attr('perid', data["id"]);
    $("#editPersonFormIdNum").empty().append(data["id"]);
    $("#editPersonFormIdCreate").empty().append(data["creation_date"]);
    $("#editPersonFormIdUpdate").empty().append(data["update_date"]);

    $(formObj + "id']").val(data["id"]);
    if(data['first_name'] != "") { 
        $(formObj + "fname']").val($.parseHTML(data["first_name"])[0].nodeValue);
    } else { 
        $(formObj + "fname']").val("");
    }
    if(data['middle_name'] != "") { 
        $(formObj + "mname']").val($.parseHTML(data["middle_name"])[0].nodeValue);
    } else { 
        $(formObj + "mname']").val("");
    }
    if(data['last_name'] != "") { 
        $(formObj + "lname']").val($.parseHTML(data["last_name"])[0].nodeValue);
    } else { 
        $(formObj + "lname']").val("");
    }
    if(data['suffix'] != "") { 
        $(formObj + "suffix']").val($.parseHTML(data["suffix"])[0].nodeValue);
    } else { 
        $(formObj + "suffix']").val("");
    }
    if(data['badge_name'] != "") { 
        $(formObj + "badge']").val($.parseHTML(data["badge_name"])[0].nodeValue);
    } else { 
        $(formObj + "badge']").val("");
    }
    if(data['address'] != "") { 
        $(formObj + "address']").val($.parseHTML(data["address"])[0].nodeValue);
    } else { 
        $(formObj + "address']").val("");
    }
    if(data['addr_2'] != "") { 
        $(formObj + "addr2']").val($.parseHTML(data["addr_2"])[0].nodeValue);
    } else { 
        $(formObj + "addr2']").val("");
    }
    if(data['city'] != "") { 
        $(formObj + "city']").val($.parseHTML(data["city"])[0].nodeValue);
    } else { 
        $(formObj + "city']").val("");
    }
    if(data['state'] != "") { 
        $(formObj + "state']").val($.parseHTML(data["state"])[0].nodeValue);
    } else { 
        $(formObj + "state']").val("");
    }
    if(data['zip'] != "") { 
        $(formObj + "zip']").val($.parseHTML(data["zip"])[0].nodeValue);
    } else { 
        $(formObj + "zip']").val("");
    }
    if(data['country'] != "") { 
        $(formObj + "country']").val($.parseHTML(data["country"])[0].nodeValue);
    } else { 
        $(formObj + "country']").val("");
    }
    if(data['email_addr'] != "") { 
        $(formObj + "email']").val($.parseHTML(data["email_addr"])[0].nodeValue);
    } else { 
        $(formObj + "email']").val("");
    }
    if(data['phone'] != "") { 
        $(formObj + "phone']").val($.parseHTML(data["phone"])[0].nodeValue);
    } else { 
        $(formObj + "phone']").val("");
    }


    $("#editPersonForm :radio[name='share_reg'][value='" + data["share_reg_ok"] + "']").prop('checked', true)
    $("#editPersonForm :radio[name='contact_ok'][value='" + data["contact_ok"] + "']").prop('checked', true)

    $("#editPersonFormIdUpdate").empty().append(data["update_date"]);
    $("#editPersonFormLastReg").empty().append(data["last_reg"]);
    $("#editPersonFormLastPickup").empty().append(data["last_badg_print"]);

    $("#editPersonForm :radio[name='active'][value='" + data["active"] + "']").prop('checked', true)
    $("#editPersonForm :radio[name='banned'][value='" + data["banned"] + "']").prop('checked', true)

    $("#editPersonForm [name='open_notes']").val(data["open_notes"]);
    $("#editPersonForm [name='admin_notes']").val(data["admin_notes"]);

    track("#editPersonForm");
    showBlock("#editPerson");
}