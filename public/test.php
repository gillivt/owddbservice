<?php

require_once("../database/initialize.php");
require_once("class.OWDDBController.php");

$course = new Course();
$course->courseref = "xxx";
$course->coursestart = "2016-02-17";
$course->hours = "40 hours";
$course->lengthofcourse = "one week";
$course->testtype = "standard";
$course->fullname = "John Doe";
$course->streetaddress = "10 Nowhere Street";
$course->town = "no Town";
$course->county = "No County";
$course->postcode = "AB12 3CD";
$course->pupiltelephone = "07999999999";
$course->drivingexperience = "None";
$course->theoryrequired = 1;
$course->testbooked = "booked on 25/02/2016";
$course->courseclaimed = 1;
$course->save();
