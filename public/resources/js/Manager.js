jQuery(document).ready(function () {
    
    var csrfToken = $("meta[name='csrftoken']").attr("content");
    
    $.ajaxSetup({
        beforeSend: function (request) {
            request.setRequestHeader("CSRF-Token", csrfToken);
        }
    });
    
    var validSystems = getSystems();
    var validRegions = getRegions();
    
    $("#route_origin").autocomplete({source: validSystems, appendTo: "#creation-modal", minLength: 3});
    $("#route_destination").autocomplete({source: validSystems, appendTo: "#creation-modal", minLength: 3});
    $("#new_system_restriction").autocomplete({source: validSystems, minLength: 3});
    $("#new_region_restriction").autocomplete({source: validRegions, minLength: 3});
    
});

function getSystems() {

    var listOfSystems;

    $.ajax({
        async: false, 
        url: "/manager/?core_action=api",
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

function getRegions() {

    var listOfRegions;

    $.ajax({
        async: false, 
        url: "/manager/?core_action=api",
        type: "POST",
        data: {"Action": "Get_Regions"},
        mimeType: "application/json",
        dataType: "json",
        success: function(result) {
            
            listOfRegions = result;

        },
        error: function(result) {}
    });

    return listOfRegions;

}
