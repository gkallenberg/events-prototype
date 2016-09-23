var Coordinates = {
    posText: 'New York City',

    isPositionSet: false,
      position: {},

    setPosition: function(position)
    {
      if (position) {
          Coordinates.isPositionSet = true;
          Coordinates.position = position;
          Coordinates.posText = Coordinates.position.coords.latitude + ', ' + Coordinates.position.coords.longitude;
          console.log(Coordinates.posText);
      }
    },

    showPosition: function()
    {
        $('#position-text').text(Coordinates.posText);
    }
}

$(document).ready(function(){

    navigator.geolocation.getCurrentPosition(Coordinates.setPosition, Coordinates.showPosition);

    // $.ajax({
    //     'url': 'https://localhost:8984/solr/events/select',
    //     'data': {'wt':'json', 'q':'*:*'},
    //     'success': function(data) {
    //         /* process e.g. data.response.docs... */
    //         console.log(data.response.numFound);
    //     },
    //     'dataType': 'jsonp',
    //     'jsonp': 'json.wrf',
    // });
    var form = $('#events-search-form');
    form.change(function() {
        // Simulate form data, but only include the selected value.
        var category = $('#events_search_category');
        var data = {};
        data[category.attr('name')] = category.val();
        console.log(data);
        // Submit data via AJAX to the form's action path.
        $.ajax({
            url : '',
            type: form.attr('method'),
            data : data,
            success: function(html) {
                console.log(data);
                // Replace current position field ...
//                $('#form_category').replaceWith(
                // ... with the returned one from the AJAX response.
//                        $(html).find('#events_search_category')
//                );
                // Position field now displays the appropriate positions.
            }
        });
    });
});
