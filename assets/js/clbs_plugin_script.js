$ = jQuery;

//create ajax connector
var connector = {
    getData: function (methodType, route, dataType, dataBlock, callbackFunction, context) {
        if (route == "")
            route = _AJAXURL;

        jQuery.ajax({
            async: true,
            type: methodType,
            dataType: dataType,
            url: route,
            data: dataBlock
        }).then(function (data) {
            if (callbackFunction != null && context != null) {
                callbackFunction(context, data);
            }
            return data;
        });
    }
};

//choose type of website (dev or live)

$(document).on('click', 'a#first.button', function () {

    $('.wrap').empty().html('<h2 class="response-heading">SOURCE DATABASE!</h2><div class="responsemessage"> <p>Everything is ready for export database!</p><p>Go to your destination website to configure plugin and than pull database form this website!</p></div><div class="response-holder"><h4>If you want to change type of database to DESTINATION click button bellow:</h4><a class="button" data-id="destination-database" href="javascript:void(0)">Make database of this website as DESTINATION database.</a></div><div id="responsebox"></div>');

});

$(document).on('click', 'a#second.button', function () {
    $('.wrap').empty().html('<h2 class="response-heading">DESTINATION DATABASE!</h2><div class="responsemessage"><form method="post"><input required="" style="width: 400px" type="text" name="source_url" placeholder="Enter source website address (e.g. www.example.rs)"><br><input type="submit" value="Insert url"></form></div><div class="response-holder"><h4>If you want to change database destination to SOURCE click button bellow:</h4><a class="button" data-id="source-database" href="javascript:void(0)">Make databes of this website as SOURCE databese.</a><br></div><div id="responsebox"></div>');

});

$(document).on('click', 'a.button', function () {

    var tagDataValue = jQuery(this).attr('data-id');
    var dataToSend = "action=insert_plugin_option&datavalue=" + tagDataValue;

    function callBackFunction(context, response) {

        if (response.database === 'destination') {
            $('.response-holder').empty().html('<h4>If you want to change database destination to SOURCE click button bellow:</h4><a class="button" data-id="source-database" href="javascript:void(0)">Make databes of this website as SOURCE databese.</a><br>');

            $('.response-heading').text('DESTINATION DATABASE');

            $('.responsemessage').empty().html('<form method="post"><input required="" style="width: 400px" type="text" name="source_url" placeholder="Enter source website address (e.g. www.example.rs)"><br><input type="submit" value="Insert url"></form>');
        }

        if (response.database === 'source') {

            $('.response-holder').empty().html('<h4>If you want to change type of database to DESTINATION click button bellow:</h4><a class="button" data-id="destination-database" href="javascript:void(0)">Make database of this website as DESTINATION database.</a>');
            $('.response-heading').text('SOURCE DATABASE!');

            $('.responsemessage').empty().html('<p>Everything is ready for export database!</p><p>Go to your destination website to configure plugin and than pull database form this website!</p>');

        }

    }

    connector.getData("POST", "", "json", dataToSend, callBackFunction, "");

});

$(document).on('click', '#pull-database', function () {

    $(this).text("Please wait...").append("&nbsp;</br><img src='http://www.toposiguranje.rs/wp-content/themes/toposiguranje/img/loader.gif' />");
    var dataToSend = "action=getPostsFromAnotherServer";

    function callBackFunction(context, response) {

        $('#pull-database').remove();
        $('.response-holder').prepend('<p>Comleted!</p>');
    }

    connector.getData("POST", "", "json", dataToSend, callBackFunction, "");

});