jQuery(document).ready(function ($) {

    // Initial path data
    var path = {debug: true};

    // Change default beat tick period
    wp.heartbeat.interval('fast'); // slow (1 beat every 60 seconds), standard (1 beat every 15 seconds), fast (1 beat every 5 seconds)

    // Initiate namespace with path data
    wp.heartbeat.enqueue('path', path, false);

    // Hook into the heartbeat-send
    jQuery(document).on('heartbeat-send.path', function (e, data) {

        // Send data to Heartbeat
        if (data.hasOwnProperty('path')) {

            if (data.path.debug === 'true') {

                console.log('Data Sent: ');
                console.log(data);
                console.log('------------------');

            } // End If Statement

        } // End If Statement

    });

    // Listen for the custom event "heartbeat-tick" on $(document).
    jQuery(document).on('heartbeat-tick.path', function (e, data) {

        // Receive Data back from Heartbeat
        if (data.hasOwnProperty('path')) {

            if (data.path.debug === 'true') {

                console.log('Data Received: ');
                console.log(data);
                console.log('------------------');

            } // End If Statement

        } // End If Statement

        // Pass data back into namespace
        wp.heartbeat.enqueue('path', data.path, false);

    });

});