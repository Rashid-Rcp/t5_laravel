<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function uploadTest(Request $request){
        $result = array();
        if ($request->hasFile('testAudio')) {
            $imageName = time().uniqid().'.'.$request->file('testAudio')->extension();  
            $path = public_path('images/'.$imageName); 
            $result['ext'] = $request->file('testAudio')->extension();
      
            while(file_exists($path)){
              $imageName = time().uniqid().'.'.$request->file('testAudio')->extension();
              $path = public_path('images/'.$imageName); 
            }
            $move =  $request->file('testAudio')->move(public_path('images'), $imageName);
            if($move){
                $result['move'] = 'ok';
            }
          }

         $result['status'] = '200';

        echo json_encode($result);
    }

    public function axiosTest(){
        $result = array('status'=>'ok');
        echo json_encode($result);
       
    }
}
