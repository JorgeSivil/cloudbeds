$(document).ready(function () {
    scheduler.config.xml_date = "%Y-%m-%d %H:%i";
    scheduler.config.details_on_create = true;
    scheduler.config.details_on_dblclick = true;
    scheduler.config.show_quick_info = false;
    scheduler.config.icons_select = ['icon_details', 'icon_delete'];
    scheduler.config.lightbox.sections = [
        {name: "Price", height: 50, type: "textarea", map_to: "text", focus: true},
        {name: "time", height: 72, type: "time", map_to: "auto"}
    ];
    scheduler.setLoadMode("day");
    scheduler.init("scheduler_here", new Date(2019, 8, 20), "week");

    scheduler.attachEvent("onBeforeEventDelete", function (id, interval) {
        let ret = false;
        $.ajax({
            cache: false,
            async: false,
            type: "DELETE",
            url: '/intervals?'
                + $.param({ from: interval.start_date.toLocaleString(), to: interval.end_date.toLocaleString() }),
            success: function () {
                ret = true;
                loadIntervals();
            },
            error: processAndAlertErrors,
            dataType: 'json'
        });
        return ret;
    });

    scheduler.attachEvent("onEventSave", function (id, interval) {
        let ret = false;
        $.ajax({
            cache: false,
            async: false,
            type: "POST",
            url: '/intervals',
            data: {
                from: interval.start_date.toLocaleString(),
                to: interval.end_date.toLocaleString(),
                price: interval.text
            },
            success: function () {
                ret = true;
                loadIntervals();
            },
            error: processAndAlertErrors,
            dataType: 'json'
        });
        return ret;
    });

    loadIntervals();

    scheduler.attachEvent("onViewChange", function () {
        loadIntervals();
    });
});

function processAndAlertErrors(jqXHR) {
    let errors = "";
    if (jqXHR.responseJSON.hasOwnProperty('message')) {
        errors += jqXHR.responseJSON.message + " \n\n";
    }
    if (jqXHR.responseJSON.hasOwnProperty('errors')) {
        $.each(jqXHR.responseJSON.errors, function (k, v) {
            errors += v + "\n";
        });
    }
    alert(errors);
    ret = false;
}

function loadIntervals() {
    let schedulerState = scheduler.getState();
    $.ajax({
        dataType: "json",
        url: "/intervals/all",
        data: {
            from: schedulerState.min_date.toLocaleString(),
            to: schedulerState.max_date.toLocaleString()
        },
        complete: function (response) {
            scheduler.clearAll();
            var intervals = response.responseJSON.data.intervals.map(function (interval) {
                return {
                    start_date: interval.from,
                    end_date: interval.to,
                    text: interval.price
                }
            })
            scheduler.parse(intervals, "json");
        }
    });
}
