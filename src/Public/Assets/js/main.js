$(document).ready(function () {
    scheduler.config.xml_date = "%Y-%m-%d %H:%i";
    scheduler.config.details_on_create = true;
    scheduler.setLoadMode("day");
    scheduler.init("scheduler_here", new Date(2019, 8, 20), "week");

    scheduler.attachEvent("onEventSave", function (id, interval) {
        let ret = false;
        $.ajax({
            cache: false,
            async: false,
            type: "POST",
            url: '/intervals/create',
            data: {
                from: interval.start_date.toLocaleString(),
                to: interval.end_date.toLocaleString(),
                price: interval.text
            },
            success: function () {
                ret = true;
                loadIntervals();
            },
            error: function (jqXHR, textStatus, errorThrown) {
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
            },
            dataType: 'json'
        });
        return ret;
    });

    loadIntervals();

    scheduler.attachEvent("onViewChange", function () {
        loadIntervals();
    });
});

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
