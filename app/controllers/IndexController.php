<?php
/**
 * Index Controller
 */
class IndexController extends Controller
{
    /**
     * Process
     */
    public function process()
    {   
        $Packages = Controller::model("Packages");
        $Packages->where("is_public", "=", 1)
                 ->orderBy("id","ASC")
                 ->fetchData();

        $this->setVariable("TrialPackage", Controller::model("GeneralData", "free-trial"))
             ->setVariable("Settings", Controller::model("GeneralData", "settings"))
             ->setVariable("Packages", $Packages);

        $this->view("index", "site");
    }
}