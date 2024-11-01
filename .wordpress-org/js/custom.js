jQuery(document).ready(function () {


    function loadEvents() {
        // Get the encoded data from the hidden field
        var encodedEvents = jQuery("#wphs-events").val();

        // Un-serialize the data.
        var eventData = decodeURIComponent(encodedEvents);

        // Return the events as JSON
        return JSON.parse(eventData);
    }


    function saveEvents() {

        var data = [];

        // Grab all the events from the calendar
        var events = jQuery('#wphs-calendar').fullCalendar('clientEvents');

        // Loop through each event object
        for (var i = 0; i < events.length; i++) {
            // Give the data variable only the data we are going to be saving in the DB
            data.push({
                id: events[i].id,
                title: events[i].title,
                pageid: events[i].pageid,
                start: events[i].start.format(),
                end: events[i].end.format()
            });
        }
        
        // Serialize data to JSON
        var eventData = encodeURIComponent(JSON.stringify(data));

        // Assign the JSON as the value to the hidden form field
        jQuery("#wphs-events").val(eventData);
    }


    jQuery('#wphs-calendar').fullCalendar({
        firstDay: 1,
        selectable: true,
        editable: true,
        lang: navigator.language,
        aspectRatio: 2.5,
        events: loadEvents(),
        eventBackgroundColor: "#23282d",
        eventBorderColor: "#23282d",
        selectOverlap: false,
        header: {
            left: 'month, agendaWeek, agendaDay',
            center: 'title',
            right: 'today, prev, next'
        },
        select: function (start, end) {
            jQuery("#wphs-dialog").dialog({
                title: "Schedule a page",
                buttons: [
                    {
                        text: "Schedule Page",
                        click: function () {
                            jQuery('#wphs-calendar').fullCalendar('renderEvent', {
                                id: jQuery.now(), // We need a "unique" id to use to reference this exact event later.
                                title: jQuery("#wphs-page option:selected").text(),
                                pageid: jQuery("#wphs-page").val(),
                                start: start,
                                end: end
                            }, true);
                            // Serialize and save events
                            saveEvents();

                            jQuery(this).dialog("close");
                        }
                    }
                ]
            }); // END dialog
        },
        eventClick: function (event) {
            // We selected this page, we gotta change the dropdown to reflect that
            jQuery("#wphs-page").val(event.pageid);

            jQuery("#wphs-dialog").dialog({
                title: "Edit a Scheduled page",
                buttons: [
                    {
                        text: "Delete",
                        click: function () {
                            // Remove the event with the proper ID and close the dialog box
                            jQuery('#wphs-calendar').fullCalendar('removeEvents', event.id);

                            // Serialize and save events
                            saveEvents();

                            // Then close dialog
                            jQuery(this).dialog("close");
                        }
                    },
                    {
                        text: "Save",
                        click: function () {
                            // Update the event title and id
                            event.title = jQuery("#wphs-page option:selected").text();
                            event.pageid = jQuery("#wphs-page").val();

                            // Update the vent in fullcalendar
                            jQuery('#wphs-calendar').fullCalendar('updateEvent', event);

                            // Serialize and save events
                            saveEvents();

                            // Then close dialog
                            jQuery(this).dialog("close");
                        }
                    }
                ]
            }); // END dialog
        },
        eventResize: function () {
            saveEvents()
        },
        eventDrop: function () {
            saveEvents()
        }
    });


    // Add clear all button to fullCalendar toolbar
    jQuery('.fc-toolbar .fc-left').prepend(
        jQuery('<button type="button" class="fc-button fc-state-default fc-corner-left fc-corner-right"><i class="dashicons dashicons-no"></i></button>')
            .on('click', function () {
                var confirmation = confirm('Are you sure you want to clear out your schedule?');
                if (confirmation) {
                    // Remove all events
                    jQuery('#wphs-calendar').fullCalendar('removeEvents');

                    // Save the empty event list
                    saveEvents();
                }
            })
    );
});