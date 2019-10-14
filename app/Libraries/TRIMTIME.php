<?php
namespace App\Libraries;

class TRIMTIME {

  public function __construct() {

  }

  //创建新项目使用
  public function trim2TimeStampString($string){
        //start 20180102
        $string=substr_replace($string, '-', 4, 0);
        //now 2018-0102
        $string=substr_replace($string, '-', 7, 0);
        //now 2018-01-02
        $string =$string.' 00:00:00';
        return $string;
  }


  
}

