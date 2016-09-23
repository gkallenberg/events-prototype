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
    $('#form_category').change(function() {
        var selected = $(this).val();
        console.log(selected);
    })
    navigator.geolocation.getCurrentPosition(Coordinates.setPosition, Coordinates.showPosition);

    $.ajax({
        'url': 'https://localhost:8984/solr/events/select',
        'data': {'wt':'json', 'q':'*:*'},
        'success': function(data) {
            /* process e.g. data.response.docs... */
            console.log(data.response.numFound);
        },
        'dataType': 'jsonp',
        'jsonp': 'json.wrf',
    });
});
