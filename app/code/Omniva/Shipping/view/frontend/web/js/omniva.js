var omniva_global_terminals_loading = false;
var omnivaGlobalSettings = [];
var omnivaGlobalData = [];
  jQuery('body').on('load-omniva-terminals', () => {
     if(jQuery('.tmjs-container').length == 0 && omniva_global_terminals_loading === false){
        loadTerminalMapping();
     }
  });

    
function loadTerminalMapping() {
  omniva_global_terminals_loading = true;
  let isModalReady = false;
  var tmjs = new TerminalMapping(omnivaGlobalSettings.api_url + '/api/v1');
  tmjs
    .sub('terminal-selected', data => {
      jQuery('input[name="order[receiver_attributes][parcel_machine_id]"]').val(data.id);
      jQuery('#order_receiver_attributes_terminal_address').val(data.name + ", " + data.address);
      jQuery('.receiver_parcel_machine_address_filled').text('');
      jQuery('.receiver_parcel_machine_address_filled').append('<div class="d-inline-flex" style="margin-top: 5px;">' +
        '<img class="my-auto mx-0 me-2" src="'+omnivaGlobalSettings.api_url + '/default_icon_icon.svg" width="25" height="25">' +
        '<h5 class="my-auto mx-0">' + data.address + ", " + data.zip + ", " + data.city + '</h5></div>' +
        '<br><a class="select_parcel_btn select_parcel_href" data-remote="true" href="#">Pakeisti</a>')
      jQuery('.receiver_parcel_machine_address_filled').show();
      jQuery('.receiver_parcel_machine_address_notfilled').hide();

      tmjs.publish('close-map-modal');
    });

  tmjs_country_code = jQuery('#order_receiver_attributes_country_code').val();
  tmjs_identifier = jQuery('#order_receiver_attributes_service_identifier').val();

  tmjs.setImagesPath(omnivaGlobalSettings.api_url + '/');
  tmjs.init({country_code: omnivaGlobalSettings.country , identifier: omnivaGlobalSettings.identifier, city: omnivaGlobalSettings.city , post_code: omnivaGlobalSettings.postcode, receiver_address: omnivaGlobalSettings.address, max_distance: omnivaGlobalSettings.max_distance});

  window['tmjs'] = tmjs;

  tmjs.setTranslation({
    modal_header: omnivaGlobalData.text_map,
    terminal_list_header: omnivaGlobalData.text_list,
    seach_header: omnivaGlobalData.text_search,
    search_btn: omnivaGlobalData.text_search,
    modal_open_btn: omnivaGlobalData.text_select_terminal,
    geolocation_btn: omnivaGlobalData.text_my_loc,
    your_position: 'Distance calculated from this point',
    nothing_found: omnivaGlobalData.text_not_found,
    no_cities_found: omnivaGlobalData.text_no_city,
    geolocation_not_supported: 'Geolocation not supported',

    // Unused strings
    search_placeholder: omnivaGlobalData.text_enter_address,
    workhours_header: 'Work hours',
    contacts_header: 'Contacts',
    select_pickup_point: omnivaGlobalData.text_select_terminal,
    no_pickup_points: 'No terminal',
    select_btn: omnivaGlobalData.text_select,
    back_to_list_btn: omnivaGlobalData.text_reset,
    no_information: omnivaGlobalData.text_not_found
  })

  tmjs.sub('tmjs-ready', function(t) {
    t.map.ZOOM_SELECTED = 8;
    isModalReady = true;
    jQuery('.spinner-border').hide();
    jQuery('.select_parcel_btn').removeClass('disabled').html(omnivaGlobalData.text_select_terminal);
    omniva_global_terminals_loading = false;
  });

  jQuery(document).on('click', '.select_parcel_btn', function(e) {
    e.preventDefault();
    if (!isModalReady) {
      return;
    }
    tmjs.publish('open-map-modal');
    coords = {lng: jQuery('.receiver_coords').attr('value-x'), lat: jQuery('.receiver_coords').attr('value-y')};
    if (coords != undefined) {
      tmjs.map.addReferencePosition(coords);
      tmjs.dom.renderTerminalList(tmjs.map.addDistance(coords), true)
    }
  });

}
