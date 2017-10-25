        <?php 
            $start = new \Moment\Moment($year."-".$month."-01 00:00:00",
                                       $AuthUser->get("preferences.timezone"));
            $start->setTimezone(date_default_timezone_get());

            if ($month == 12) {
                $end = ($year + 1) . "-01-01 00:00:00";
            } else {
                $end = $year . "-" . sprintf("%02d", $month + 1) . "-01 00:00:00";
            }
            $end = new \Moment\Moment($end, $AuthUser->get("preferences.timezone"));
            $end->setTimezone(date_default_timezone_get());

            $Posts = Controller::model("Posts");
            $Posts->where(TABLE_PREFIX.TABLE_POSTS.".user_id", "=", $AuthUser->get("id"))
                  ->where("is_scheduled", "=", 1)
                  ->where("schedule_date", ">=", $start->format("Y-m-d H:i:s"))
                  ->where("schedule_date", "<", $end->format("Y-m-d H:i:s"))
                  ->fetchData();

            $counts = [];
            foreach ($Posts->getData() as $p) {
                $sd = new \Moment\Moment($p->schedule_date, date_default_timezone_get());
                $sd->setTimezone($AuthUser->get("preferences.timezone"));

                $daynumber = $sd->format("d");

                if (empty($counts[$daynumber])) {
                    $counts[$daynumber] = 0;
                }

                $counts[$daynumber]++;
            }
        ?>

        <div class='skeleton' id="schedule-calendar-month">
            <div class="container-1200">
                <div class="row clearfix">
                    <div class="sc-month-switch">
                        <?php 
                            $prevmonth = $month > 1 ? $month - 1 : "12";
                            $prevmonth = sprintf('%02d', $prevmonth);

                            $nextmonth = $month < 12 ? $month + 1 : "01";
                            $nextmonth = sprintf('%02d', $nextmonth);

                            $date = new Moment\Moment($year."-".$month."-01", 
                                                      $AuthUser->get("preferences.timezone"));
                        ?>

                        <div class="month">
                            <a class="sli sli-arrow-left nav left" href="<?= APPURL."/schedule-calendar/".($prevmonth == "12" ? $year-1 : $year)."/".$prevmonth; ?>"></a>
                            <?= $date->format("F") ?>
                            <a class="sli sli-arrow-right nav right" href="<?= APPURL."/schedule-calendar/".($nextmonth == "01" ? $year+1 : $year)."/".$nextmonth; ?>"></a>
                        </div>

                        <div class="year"><?= $year ?></div>
                    </div>

                    <div class="schedule-calendar">
                        <?php 
                            $short_week_days = [
                                __("Mon"), __("Tue"), __("Wed"), __("Thu"), 
                                __("Fri"), __("Sat"), __("Sun")
                            ];
                        ?>
                        <div class="sc-head clearfix">
                            <?php foreach ($short_week_days as $wd): ?>
                                <div class='cell'><?= $wd ?></div>
                            <?php endforeach ?>
                        </div>

                        <?php 
                            $days_in_month = date("t", mktime(0, 0, 0, (int)$month, 1, $year));
                            $month_firstday_number = date("N", mktime(0, 0, 0, (int)$month, 1, $year));
                            $month_lastday_number = date("N", mktime(0, 0, 0, (int)$month, $days_in_month, $year));

                            $days_in_prev_month = date("t", mktime(0, 0, 0, (int)$prevmonth, 1, $prevmonth == "12" ? $year-1 : $year));
                            $days_in_next_month = date("t", mktime(0, 0, 0, (int)$nextmonth, 1, $nextmonth == "01" ? $year+1 : $year));

                            $now = new Moment\Moment("now", date_default_timezone_get());
                            $now->setTimezone($AuthUser->get("preferences.timezone"));
                        ?>
                        <div class="clearfix">
                            <?php if ($month_firstday_number > 1): ?>
                                <?php for ($i=1; $i<$month_firstday_number; $i++): ?>
                                    <?php 
                                        $day = $days_in_prev_month - ($month_firstday_number-1-$i);
                                        $date = ($prevmonth == "12" ? $year-1 : $year) . "-". $prevmonth . "-" . sprintf("%02d", $day);
                                        $date = new Moment\Moment($date, $AuthUser->get("preferences.timezone"));
                                    ?>
                                    <div class='cell in-other-month'>
                                        <div class='cell-inner'>
                                            <span class="day-name"><?= $date->format("D") ?></span>
                                            <span class="day-number"><?= $day ?></span>

                                            <a href="<?= APPURL."/schedule-calendar/".$date->format("Y/m") ?>" class="full-link"></a>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            <?php endif ?>
                            
                            <?php for ($day=1; $day<=$days_in_month; $day++): ?>
                                <?php 
                                    $date = $year . "-". $month . "-" . sprintf("%02d", $day);
                                    $date = new Moment\Moment($date, $AuthUser->get("preferences.timezone"));
                                ?>
                                <div class="cell <?= $date->format("Y-m-d") == $now->format("Y-m-d") ? "today" : "" ?>">
                                    <div class='cell-inner'>
                                        <span class="day-name"><?= $date->format("D") ?></span>
                                        <span class="day-number"><?= $day ?></span>

                                        <?php if ($date->format("Y-m-d") >= $now->format("Y-m-d")): ?>
                                            <a class="add-post" href="<?= APPURL."/post?date=".$date->format("Y-m-d") ?>">
                                                <span class="sli sli-plus icon"></span>
                                                <span class="hide-on-medium-and-down">
                                                    <?= __("Add post") ?>
                                                </span>
                                            </a>
                                        <?php endif ?>

                                        <?php if (!empty($counts[$date->format("d")])): ?>
                                            <?php
                                                $count = $counts[$date->format("d")];
                                                $count_class="";
                                                if ($count > 10) {
                                                    $count_class = "high";
                                                } else if ($count > 5) {
                                                    $count_class = "medium";
                                                }

                                            ?>
                                            <div class="count <?= $count_class ?>">
                                                <a href="<?= APPURL."/schedule-calendar/".$date->format("Y/m/d") ?>">
                                                    <?= n__("%s scheduled post", "%s scheduled posts", $count, $count) ?>
                                                </a>
                                            </div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            <?php endfor; ?>


                            <?php if ($month_lastday_number < 7): ?>
                                <?php $day = 1; ?>
                                <?php for ($i=$month_lastday_number; $i<7; $i++): ?>
                                    <?php 
                                        $date = ($nextmonth == "01" ? $year+1 : $year) . "-". $nextmonth . "-" . sprintf("%02d", $day);
                                        $date = new Moment\Moment($date, $AuthUser->get("preferences.timezone"));
                                    ?>
                                    <div class='cell in-other-month'>
                                        <div class='cell-inner'>
                                            <span class="day-name"><?= $date->format("D") ?></span>
                                            <span class="day-number"><?= $day++ ?></span>

                                            <a href="<?= APPURL."/schedule-calendar/".$date->format("Y/m") ?>" class="full-link"></a>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>