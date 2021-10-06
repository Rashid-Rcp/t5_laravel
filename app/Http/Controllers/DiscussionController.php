<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;
class DiscussionController extends Controller
{
    //
    public function create(Request $request){

       // return json_encode(array('status'=>'ok'));
        $user = $request->input('user');
        $club = $request->input('club');
        $title = $request->input('title');
        $topic_voice = '';
        $vote = $request->input('vote');
        $comment = $request->input('comment');
        $tags = $request->input('tags');
        $participants = $request->input('participants');
        $clubID = DB::table('club')->where('name',$club)->pluck('id')->toArray()[0];
        if(!$clubID){
            return json_encode(
                array(
                    'status'=>'error'
                )
                );
        }
        $date = Carbon::now();
        $year = $date->format('Y');
        $month = $date->format('m');
        $filePath = public_path('voice/club/'.$year .'/'.$month);
        if (!file_exists($filePath)) {
                mkdir($filePath, 0777, true);
        }
        if ($request->hasFile('topic_voice')) {
            $voice = time().uniqid().'.'.$request->file('topic_voice')->extension();  
            $path = $filePath.'/'.$voice; 
            while(file_exists($path)){
                $voice = time().uniqid().'.'.$request->file('topic_voice')->extension();
                $path = $filePath.'/'.$voice; 
            }
            $request->file('topic_voice')->move($filePath, $voice);
            $topic_voice = $year .'/'.$month.'/'.$voice;
        }
        $id = DB::table('discussion')->insertGetId([
            'club_id'=>$clubID,
            'creator_id'=>$user,
            'topic'=>$title,
            'description'=>'',
            'description_audio'=>$topic_voice,
            'participants'=>$participants,
            'comment'=>$comment,
            'vote'=>$vote,
            'tags'=>$tags,
            'status'=>'open',
            'date'=>$date
        ]);

        if($id){
            return json_encode(array(
                'status'=>'success',
                'id'=>$id,
            ));
        }else{
            return json_encode(array(
                'status'=>'error'
            ));
        }

    
    }
}
