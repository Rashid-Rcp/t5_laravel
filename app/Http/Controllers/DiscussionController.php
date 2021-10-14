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
        $duration = $request->input('duration');
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
            'audio_duration'=>$duration,
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

    public function getUserDiscussion($userId){
       
        $votes = DB::table('discussion_vote')
        ->select('discussion_id',DB::raw('COUNT(id) AS votes'))
        ->groupBy('discussion_id');

        $comments = DB::table('discussion_comment')
        ->select('discussion_id',DB::raw('COUNT(id) AS comments'))
        ->groupBy('discussion_id');

        $answers = DB::table('discussion_answer')
        ->select('discussion_id',DB::raw('COUNT(id) AS answers'))
        ->groupBy('discussion_id');


        // $isVoted = DB::table('discussion_vote')
        // ->select('discussion_id',DB::raw('COUNT(id) AS isVoted'))
        // ->where('user_id',$userId)->groupBy('discussion_id');
       
        $discussions = DB::table('discussion')
        ->join('club','discussion.club_id','=','club.id')
        ->leftJoinSub($votes,'votes',function($join){
            $join->on('votes.discussion_id','=','discussion.id');
          })
          ->leftJoinSub($answers,'answers',function($join){
            $join->on('answers.discussion_id','=','discussion.id');
          })
          ->leftJoinSub($comments,'comments',function($join){
            $join->on('comments.discussion_id','=','discussion.id');
          })
          
        ->whereIn('discussion.club_id', function($query) use($userId){
            $query->select('club_id')
            ->from('club_members')
            ->where('member_id',$userId)
            ->where('role','admin');
        })
        ->where('discussion.status','open')
        ->select('discussion.id','discussion.topic','discussion.vote','discussion.comment','discussion.status','discussion.date','votes.votes','comments.comments','answers.answers','club.name as club')
        ->orderBy('discussion.date' ,'desc')
        ->get();
       
        foreach($discussions as $key=>$discussion){
            $discussions[$key]->time = $this->dateCalculator($discussion->date);
        }
        if($discussions){
            return json_encode(array(
                'status'=>'success',
                'discussions'=>$discussions
            ));
        }
        else{
            return json_encode(array(
                'status'=>'error',
            ));
        } 
    }

    public function dateCalculator($date){
        $date = new Carbon($date);
        $now = Carbon::now();
        $time = $date->diffInYears($now);
        $unit = $time === 1?'year':'years';
        if(!$time){
            $time = $date->diffInMonths($now);
            $unit = $time === 1?'month':'months';
        }
        if(!$time){
            $time = $date->diffInDays($now);
            $unit = $time === 1?'day':'days';
        }
        if(!$time){
            $time = $date->diffInHours($now);
            $unit = $time === 1?'hour':'hours';
        }
        if(!$time){
            $time = $date->diffInMinutes($now);
            $unit = $time === 1?'minute':'minutes';
        }
        if(!$time){
            $time = 'Just now';
            $unit = '';
        }
        if($unit){
            $unit .= ' ago';
        }
       return( $time .' '.$unit);
    }

    public function getDiscussionManage($discussionId){
       

        $discussion = DB::table('discussion')
        ->join('club','club.id','=','discussion.club_id')
        ->join('users','users.id','=','discussion.creator_id')
        ->select('discussion.*','club.name as club','users.name as creator')
        ->where('discussion.id',$discussionId)
        ->get();
        $participant_ids = json_decode($discussion[0]->participants);
        $participants = DB::table('users')
        ->whereIn('id',$participant_ids)
        ->select('id','image','name')->get();
        $creator = DB::table('users')->where('id',$discussion[0]->creator_id)->pluck('name');
        $date = $this->dateCalculator($discussion[0]->date);
        $votes = [];
        $total_votes = 0;
        if($discussion[0]->vote === 'true'){
            foreach($participant_ids as $participant){
                $p_votes = DB::table('discussion_vote')
                ->join('users','users.id','=','discussion_vote.participant_id')
                ->where('discussion_vote.participant_id',$participant)
                ->select('users.id as user_id' ,'users.image' ,'users.name', DB::raw('COUNT(discussion_vote.id) AS votes'))
                ->groupBy('discussion_vote.participant_id','users.id','users.image','users.name')->get();
                array_push($votes,array($participant=>$p_votes));
            }
            $t_votes = DB::table('discussion_vote')
            ->where('discussion_id',$discussionId)
            ->select(DB::raw('COUNT(id) AS total_votes'))
            ->groupBy('discussion_id')->get();
           $total_votes =  $t_votes[0]->total_votes;

        }
        $comments =[];
        $total_comments = 0;
        if($discussion[0]->comment === 'true'){
            $comments = DB::table('discussion_comment')
            ->join('users','discussion_comment.user_id','=','users.id')
            ->where('discussion_id',$discussionId)
            ->select('users.id as user_id','users.image as user_image','users.name as user_name','discussion_comment.comment')
            ->get();
            $t_comments = DB::table('discussion_comment')
            ->where('discussion_id',$discussionId)
            ->select(DB::raw('COUNT(id) AS total_comments'))
            ->orderBy('discussion_comment.id','desc')
            ->groupBy('discussion_id')->take(5)->get();
           $total_comments =  $t_comments[0]->total_comments;
        }
        $discussion[0]->{'participants_data'} = $participants;
        $discussion[0]->{'total_votes'} = $total_votes;
        $discussion[0]->{'votes'} = $votes;
        $discussion[0]->{'total_comments'} = $total_comments;
        $discussion[0]->{'comments'} = $comments;
        $discussion[0]->{'creator'} = $creator[0];
        $discussion[0]->{'date'} = $date;

        return json_encode(array('status'=>'success','data'=>$discussion));
    }

    public function getDiscussionAllComments($discussionId){
        $comments = DB::table('discussion_comment')
        ->join('users','discussion_comment.user_id','=','users.id')
        ->where('discussion_id',$discussionId)
        ->select('users.id as user_id','users.image as user_image','users.name as user_name','discussion_comment.comment')
        ->get();
        if($comments){
            return json_encode(
                array('status'=>'success','comments'=>$comments)
            );
        }
    }
}
