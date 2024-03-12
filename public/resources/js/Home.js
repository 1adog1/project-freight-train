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

    var copyPopoverTriggerList = [].slice.call($(".copy-out"))
    var copyPopoverList = copyPopoverTriggerList.map(function (eachPopover) {
      return new bootstrap.Popover(eachPopover, {trigger: "manual"})
    })

    var overridePopoverTriggerList = [].slice.call($(".override-popover"))
    var overridePopoverList = overridePopoverTriggerList.map(function (eachPopover) {
      return new bootstrap.Popover(eachPopover, {trigger: "focus"})
    })

    $(".copy-out").click(function() {
        navigator.clipboard.writeText($(this).attr("data-copy-value"));

        var thisPopover = bootstrap.Popover.getInstance($(this));

        thisPopover.show();
        setTimeout(function () {
            thisPopover.hide();
        },
        2000);
    });
    
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
