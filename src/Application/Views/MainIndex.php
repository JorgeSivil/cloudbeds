<div id="scheduler_here" class="dhx_cal_container"
     style='width:100%; height:100%;'>
    <div class="dhx_cal_navline">
        <div class="dhx_cal_prev_button">&nbsp;</div>
        <div class="dhx_cal_next_button">&nbsp;</div>
        <div class="dhx_cal_today_button"></div>
        <div class="dhx_cal_date"></div>
        <div class="dhx_cal_tab" name="day_tab"></div>
        <div class="dhx_cal_tab" name="week_tab"></div>
        <div class="dhx_cal_tab" name="month_tab"></div>
    </div>
    <div class="dhx_cal_header"></div>
    <div class="dhx_cal_data"></div>
</div>
<script>
    scheduler.config.xml_date="%Y-%m-%d %H:%i";
    scheduler.init("scheduler_here", new Date(2019, 8, 20), "week");
    scheduler.setLoadMode("day");
    scheduler.load("/intervals/all", "json");

    var dp = new dataProcessor("/events");
    dp.setTransactionMode("REST"); // use to transfer data with REST
    dp.init(scheduler);
</script>
