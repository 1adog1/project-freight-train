jQuery(document).ready(function () {
    
    var csrfToken = $("meta[name='csrftoken']").attr("content");
    
    $.ajaxSetup({
        beforeSend: function (request) {
            request.setRequestHeader("CSRF-Token", csrfToken);
        }
    });
    
    var validSystems = getSystems();
    
    $("#origin").autocomplete({source: validSystems, minLength: 3});
    $("#destination").autocomplete({source: validSystems, minLength: 3});
    
});

function getSystems() {

    var listOfSystems;

    $.ajax({
        async: false, 
        url: "/home/?core_action=api",
        type: "POST",
        data: {"Action": "Get_Systems"},
        mimeType: "application/json",
        dataType: "json",
        success: function(result) {
            
            listOfSystems = result;

        },
        error: function(result) {}
    });

    return listOfSystems;

}
