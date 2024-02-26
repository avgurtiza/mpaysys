<?php

if(!function_exists("help_me")) {
    function help_me() {
        echo "If you can.";
    }
}

if(!function_exists("activity")) {
    function activity(): Activity
    {
        return new Activity(new Messerve_Model_Eloquent_Activity());
    }
}
