<?php

namespace App\Http\Controllers;

use DateTime;
use GrahamCampbell\ResultType\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    //
    public function userClub($userId){
        //echo json_encode($request);
       $result = array(
           'status'=>'not_found'
       );
       $club =  DB::table('club')
          ->where('creator_id',$userId)
          ->select('id')
          ->first();
        if($club){
            $result = array(
                'status'=>'success',
                'club'=>$club
            );
        }
        echo json_encode($result);
    }

    public function create(Request $request){
        $user = $request->input('user');
        $clubName = $request->input('clubName');
        $clubTags = $request->input('clubTags');
        $clubDescription = $request->input('clubDescription');
        $clubType = $request->input('clubType');
        $imageName = 't5-club-icon.png';
        if ($request->hasFile('image')) {
            $imageName = time().uniqid().'.'.$request->file('image')->extension();  
            $path = public_path('images/club/'.$imageName); 
      
            while(file_exists($path)){
              $imageName = time().uniqid().'.'.$request->file('image')->extension();
              $path = public_path('images/club/'.$imageName); 
            }
            $request->file('image')->move(public_path('images/club'), $imageName);
        }

        $isClubExist = DB::table('club')
        ->where('name',$clubName)
        ->select('id')
        ->first();
        if($isClubExist){
            $result = array(
                'status'=>'club_exist'
            );
            return json_encode($result);
        }
        $date = new DateTime();
        $club = DB::table('club')
        ->insertGetId([
            'creator_id'=>$user,
            'name'=>$clubName,
            'tags'=>$clubTags,
            'description'=>$clubDescription,
            'created_at'=>$date,
            'image'=> $imageName,
            'type'=>$clubType
        ]);
        if($club){
            $result = array(
                'status'=>'success',
                'club'=>$club
            );
            return json_encode($result);
        }
        else{
            $result = array(
                'status'=>'failed'
            );
            return json_encode($result);
        }
    }

    public function addMembers(Request $request){
        $members = $request->input('members');
        $club = $request->input('clubId');
        foreach($members as $member){
            $data = array(
                'club_id'=>$club,
                'member_id'=>$member,
                'role'=>'member'
            );
            DB::table('club_members')
            ->insert($data);
        }
        return json_encode(
            array('status'=>'success')
        );
       
    }
}