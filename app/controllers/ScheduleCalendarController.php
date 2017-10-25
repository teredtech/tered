<?php
/**
 * ScheduleCalendar Controller
 */
class ScheduleCalendarController extends Controller
{
    /**
     * Process
     */
    public function process()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        if (!$AuthUser){
            header("Location: ".APPURL."/login");
            exit;
        } else if ($AuthUser->isExpired()) {
            header("Location: ".APPURL."/expired");
            exit;
        }


        if (Input::post("action") == "remove") {
            $this->remove();
        }


        if (isset($Route->params->day)) {
            $this->dayView();
        } else {
            $this->monthView();
        }
    }


    /**
     * Generate month view
     * @return null 
     */
    private function monthView()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");

        // Check and validate date
        $year = isset($Route->params->year) ? $Route->params->year : 0;
        $month = isset($Route->params->month) ? $Route->params->month : 0;

        if (!isValidDate($year."-".$month."-01", "Y-m-d")) {
            $now = new Moment\Moment("now", date_default_timezone_get());
            $now->setTimezone($AuthUser->get("preferences.timezone"));

            $year = $now->format("Y");
            $month = $now->format("m");

            header("Location: ".APPURL."/schedule-calendar/".$year."/".$month);
            exit;
        }

        $this->setVariable("month", $month)
             ->setVariable("year", $year)
             ->setVariable("viewtype", "month");

        $this->view("schedule-calendar");
    }


    /**
     * Generate day view
     * @return null 
     */
    private function dayView()
    {
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        
        // Check validate date
        $day = $Route->params->day;
        $year = $Route->params->year;
        $month = $Route->params->month;

        if (!isValidDate($year."-".$month."-".$day, "Y-m-d")) {
            if (isValidDate($year."-".$month."-01", "Y-m-d")) {
                $url = APPURL."/schedule-calendar/".$year."/".$month;
            } else {
                $url = APPURL."/schedule-calendar/";
            }

            header("Location: ".$url);
            exit;
        }


        // Get accounts
        $Accounts = Controller::model("Accounts");
        $Accounts->where("user_id", "=", $AuthUser->get("id"))
                 ->orderBy("id","DESC")
                 ->fetchData();


        if ($Accounts->getTotalCount() > 0) {
            // Get schedule counts for each accounts
            $start = new \Moment\Moment("$year-$month-$day 00:00:00",
                                             $AuthUser->get("preferences.timezone"));
            $start->setTimezone(date_default_timezone_get());
            $end = new \Moment\Moment("$year-$month-$day 23:59:59",
                                             $AuthUser->get("preferences.timezone"));
            $end->setTimezone(date_default_timezone_get());

            $query = DB::table(TABLE_PREFIX.TABLE_POSTS)
                     ->select([DB::raw("COUNT(id) as total"), "account_id"])
                     ->where("user_id", "=", $AuthUser->get("id"))
                     ->where("is_scheduled", "=", 1)
                     ->where("schedule_date", ">=", $start->format("Y-m-d H:i:s"))
                     ->where("schedule_date", "<", $end->format("Y-m-d H:i:s"))
                     ->groupBy("account_id");
            $res = $query->get();

            $count_per_account = [];
            foreach ($res as $r) {
                $count_per_account[$r->account_id] = $r->total;
            }

            $ActiveAccount = Controller::model("Account", Input::get("account"));
            if (!$ActiveAccount->isAvailable() || 
                $ActiveAccount->get("user_id") != $AuthUser->get("id")) {

                foreach ($Accounts->getDataAs("Account") as $a) {
                    if (!empty($count_per_account[$a->get("id")])) {
                        $ActiveAccount = $a;
                        break;
                    }
                }

                if (!$ActiveAccount->isAvailable()) {
                    $a = $Accounts->getDataAs("Account");
                    $ActiveAccount = $a[0];
                }
            }


            // Get Posts
            $Posts = Controller::model("Posts");
            $Posts->where(TABLE_PREFIX.TABLE_POSTS.".account_id", "=", $ActiveAccount->get("id"))
                  ->where("is_scheduled", "=", 1)
                  ->where("schedule_date", ">=", $start->format("Y-m-d H:i:s"))
                  ->where("schedule_date", "<", $end->format("Y-m-d H:i:s"))
                  ->fetchData();

            $in_progress = 0;
            $completed = 0;
            foreach ($Posts->getData() as $p) {
                if (in_array($p->status, ["failed", "published"])) {
                    $completed++;
                } else {
                    $in_progress++;
                }
            }

            $this->setVariable("Posts", $Posts)
                 ->setVariable("ActiveAccount", $ActiveAccount)
                 ->setVariable("count_per_account", $count_per_account)
                 ->setVariable("in_progress", $in_progress)
                 ->setVariable("completed", $completed);
        }


        $this->setVariable("year", $year)
             ->setVariable("month", $month)
             ->setVariable("day", $day)
             ->setVariable("viewtype", "day")
             ->setVariable("Accounts", $Accounts);

        $this->view("schedule-calendar");   
    }


    /**
     * Remove Post
     * @return void
     */
    private function remove()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");

        if (!Input::post("id")) {
            $this->resp->msg = __("ID is requred!");
            $this->jsonecho();
        }

        $Post = Controller::model("Post", Input::post("id"));

        if (!$Post->isAvailable() || 
            $Post->get("user_id") != $AuthUser->get("id") ||
            in_array($Post->get("status"), ["published", "publishing"])) 
        {
            $this->resp->msg = __("Invalid ID");
            $this->jsonecho();
        }

        $Post->delete();

        $this->resp->result = 1;
        $this->jsonecho();
    }
}