<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Events\discussionAnswer;
use App\Events\discussionVote;
use App\Events\discussionComment;
use Carbon\Carbon;
use App\Models\Discussion;
use DateTime;
use GrahamCampbell\ResultType\Result;
use Illuminate\Support\Facades\Date;

class DiscussionController extends Controller
{
    //
    public function create(Request $request){

        //return json_encode(array('status'=>'ok'));
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
       
      
        // $id = DB::table('discussion')->insertGetId([
        //     'club_id'=>$clubID,
        //     'creator_id'=>$user,
        //     'topic'=>$title,
        //     'description'=>'',
        //     'description_audio'=>$topic_voice,
        //     'audio_duration'=>$duration,
        //     'comment'=>$comment,
        //     'vote'=>$vote,
        //     'tags'=>$tags,
        //     'status'=>'open',
        //     'date'=>$date
        // ]);

        $discussion = new Discussion;

        // $inserted_data = Discussion::create([
        //     'club_id'=>$clubID,
        //     'creator_id'=>$user,
        //     'topic'=>$title,
        //     'description'=>'',
        //     'description_audio'=>$topic_voice,
        //     'audio_duration'=>$duration,
        //     'comment'=>$comment,
        //     'vote'=>$vote,
        //     'tags'=>$tags,
        //     'status'=>'open',
        //     'date'=>$date
        // ]);

        $discussion->club_id = $clubID;
        $discussion->creator_id = $user;
        $discussion->topic = $title;
        $discussion->description = '';
        $discussion->description_audio = $topic_voice;
        $discussion->audio_duration = $duration;
        $discussion->comment = $comment;
        $discussion->vote = $vote;
        $discussion->tags = $tags;
        $discussion->status = 'open';
        $discussion->date = $date;
        $discussion->save();
        //$discussion->searchable();
        $discussion->unsearchable();
        $id =$discussion->id;
        if($id){
            $participant_list = array();
            foreach(json_decode($participants) as $participant){
                $participant_list[]= array(
                    'discussion_id'=>$id,
                    'participant_id'=>$participant
                );
            }
            DB::table('discussion_participants')
            ->insert($participant_list);
        }

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

    public function remove(){
        $discussion = new Discussion;
        $discussion->where('id','=',10)->unsearchable();
        echo 'oko';
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
        $participant_ids = DB::table('discussion_participants')
        ->where('discussion_id',$discussionId)->pluck('participant_id')->toArray();
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
                ->where('discussion_vote.discussion_id',$discussionId)
                ->select('users.id as user_id' ,'users.image' ,'users.name', DB::raw('COUNT(discussion_vote.id) AS votes'))
                ->groupBy('discussion_vote.participant_id','users.id','users.image','users.name')->get();
                array_push($votes,array($participant=>$p_votes));
            }
            $t_votes = DB::table('discussion_vote')
            ->where('discussion_id',$discussionId)
            ->select(DB::raw('COUNT(id) AS total_votes'))
            ->groupBy('discussion_id')->get();
           $total_votes = count($t_votes) > 0 ?  $t_votes[0]->total_votes:0;
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
           $total_comments = count($t_comments) > 0 ?  $t_comments[0]->total_comments:0;
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

    public function getUserFollowDiscussionAll($userId){
        $discussions = DB::table('discussion')
        ->join('club','discussion.club_id','=','club.id')
        ->whereIn('discussion.club_id', function ($query) use($userId){
            $query->select('club_id')
            ->from('club_members')
            ->where('member_id',$userId);
        })
        ->select('discussion.id','discussion.topic','discussion.status','discussion.date','club.name as club')
        ->selectSub(function ($query) use($userId){
            /** @var $query \Illuminate\Database\Query\Builder */
            $query->from('discussion_participants')
                  ->selectRaw('COUNT(*)')
                  ->where('discussion_participants.participant_id', '=', $userId)
                  ->whereRaw('`discussion_participants`.`discussion_id` = `discussion`.`id`');
    
        }, 'participant')
        ->orderBy('discussion.date','desc')
        ->get();
        foreach($discussions as $key=>$discussion){
            $discussions[$key]->time = $this->dateCalculator($discussion->date);
        }

     return json_encode(
         array('status'=>'success',
         'discussions'=>$discussions
         )
     );
    }

    public function getUserFollowDiscussionParticipant($userId){

        $discussions = DB::table('discussion')
        ->join('club','discussion.club_id','=','club.id')
        ->join('discussion_participants', function($join)  use($userId){
            $join->on('discussion_participants.discussion_id','=','discussion.id')
            ->where('discussion_participants.participant_id','=',$userId);
        })
        ->whereIn('discussion.club_id', function ($query) use($userId){
            $query->select('club_id')
            ->from('club_members')
            ->where('member_id',$userId);
        })
        ->select('discussion.id','discussion.topic','discussion.status','discussion.date','club.name as club' ,'discussion_participants.id as participant')
        
        ->orderBy('discussion.date','desc')
        ->get();
        foreach($discussions as $key=>$discussion){
            $discussions[$key]->time = $this->dateCalculator($discussion->date);
        }

     return json_encode(
         array('status'=>'success',
         'discussions'=>$discussions
         )
     );

    }

    public function discussionAnswer(Request $request){
        $participant = $request->input('user_id');
        $discussion = $request->input('discussion_id');
        $text = $request->input('answer_text');
        $duration = $request->input('answer_duration');
        $answer_voice = '';
        $date = Carbon::now();
        $year = $date->format('Y');
        $month = $date->format('m');
        $filePath = public_path('voice/club/'.$year .'/'.$month);
        if (!file_exists($filePath)) {
                mkdir($filePath, 0777, true);
        }
        if ($request->hasFile('answer_voice')) {
            $voice = time().uniqid().'.'.$request->file('answer_voice')->extension();  
            $path = $filePath.'/'.$voice; 
            while(file_exists($path)){
                $voice = time().uniqid().'.'.$request->file('answer_voice')->extension();
                $path = $filePath.'/'.$voice; 
            }
            $request->file('answer_voice')->move($filePath, $voice);
            $answer_voice = $year .'/'.$month.'/'.$voice;
        }

        $id = DB::table('discussion_answer')
        ->insertGetId([
            'discussion_id'=>$discussion,
            'user_id'=>$participant,
            'audio'=>$answer_voice,
            'audio_duration'=>$duration,
            'text'=>$text,
        ]);
        $user_data = DB::table('users')
        ->select('id','image','name')->where('id',$participant)->first();
        $answer = 
            array(
                'audio'=>$answer_voice,
                'audio_duration'=>$duration,
                'text'=>$text,
                'participant'=>$user_data->name,
                'participant_dp'=>$user_data->image,
                'participant_id'=>$user_data->id,

            );


        event(new discussionAnswer($answer,$discussion)); // socket trigger

        if($id){
            return json_encode(
                array(
                    'status'=>'success',
                    'id'=>$id,
                )
            ) ;
        }
    }

    public function discussionDetails($discussionId){

        $votes = DB::table('discussion_vote')
        ->select('discussion_id',DB::raw('COUNT(id) AS votes'))
        ->groupBy('discussion_id');

        $comments = DB::table('discussion_comment')
        ->select('discussion_id',DB::raw('COUNT(id) AS comments'))
        ->groupBy('discussion_id');
        $participants = DB::table('discussion_participants')
        ->where('discussion_id','=',$discussionId)->pluck('participant_id')->toArray();

        $discussion = DB::table('discussion')
        ->join('club','club.id','=','discussion.club_id')
        ->join('users','users.id','=','discussion.creator_id')
        ->leftJoinSub($votes,'votes',function($join){
            $join->on('votes.discussion_id','=','discussion.id');
          })
        ->leftJoinSub($comments,'comments',function($join){
            $join->on('comments.discussion_id','=','discussion.id');
          })
        ->select('discussion.id','discussion.topic','discussion.description_audio','discussion.audio_duration','discussion.date','discussion.vote','discussion.comment',
        'votes.votes','comments.comments',
        'club.name as club','club.image as dp','users.name as creator','users.image as creator_dp'
        )
        ->where('discussion.id','=',$discussionId)
        ->get();
        $discussion[0]->{'time'} = $this->dateCalculator($discussion[0]->date);
        $discussion[0]->{'participants'} = $participants;
        $discussionAnswers = DB::table('discussion_answer')
        ->join('users','users.id','=','discussion_answer.user_id')
        ->select('discussion_answer.audio','discussion_answer.audio_duration','discussion_answer.text','users.name as participant','users.image as participant_dp')
        ->where('discussion_answer.discussion_id','=',$discussionId)
        ->orderBy('discussion_answer.id','asc')
        ->simplePaginate(10);
        return json_encode(
            array(
                'status'=>'success',
                'discussion'=>$discussion,
                'answers'=>$discussionAnswers
            )
        );
    }

    public function getParticipantWithVote ($discussionId){
        $votes = DB::table('discussion_vote')
        ->select('participant_id','discussion_id',DB::raw('COUNT(id) AS votes'))
        ->groupBy('participant_id','discussion_id');
        $participants= DB::table('discussion_participants')
        ->join('users','discussion_participants.participant_id','=','users.id')
        ->leftJoinSub($votes,'votes',function($join) use($discussionId) {
            $join->on('votes.participant_id','=','discussion_participants.participant_id')
            ->where('votes.discussion_id', '=', $discussionId);
          })
        ->where('discussion_participants.discussion_id','=',$discussionId)
        ->select('users.id','users.name','users.image','votes.votes')
        ->orderBy('users.id','asc')
        ->get();

        return $participants;
    }
    public function getDiscussionTotalVotes ($discussionId){
        
        $total_votes = DB::table('discussion_vote')
        ->where('discussion_id','=',$discussionId)
        ->get()->count();
        return $total_votes;
    }

    public function discussionVotes($discussionId,$userId){
       
        $participants = $this->getParticipantWithVote($discussionId);
        $vote_for = DB::table('discussion_vote')
        ->select('participant_id')
        ->where('discussion_id','=',$discussionId)
        ->where('user_id','=',$userId)
        ->first();
        $total_votes = $this->getDiscussionTotalVotes($discussionId);
        return json_encode(
            array(
                'status'=>'success',
                'participants'=>$participants,
                'total_votes'=>$total_votes,
                'vote_for'=>$vote_for
            )
        );
    }

    public function postDiscussionVotes(Request $request){
        $user = $request->input('userId');
        $participant = $request->input('participantId');
        $discussion = $request->input('discussionId');
        date_default_timezone_set("Asia/Kolkata");
        $date = Carbon::now();
        $isVoted =  DB::table('discussion_vote')
        ->select('id')
        ->where('discussion_id','=',$discussion)
        ->where('user_id','=',$user)
        ->first();
        if(!$isVoted){
            $id = DB::table('discussion_vote')->insertGetId([
                'discussion_id'=>$discussion,
                'participant_id'=>$participant,
                'user_id'=>$user,
                'date'=>$date
            ]);
        }
        else{
            $id = DB::table('discussion_vote')
            ->where('discussion_id','=',$discussion)
            ->where('user_id','=',$user)
            ->update(['participant_id'=>$participant]);
        }
        $participants = $this->getParticipantWithVote($discussion);
        $total_votes = $this->getDiscussionTotalVotes($discussion);
        event(new discussionVote($discussion,$participants,$total_votes)); // socket trigger

        return json_encode(
            array(
                'status'=>'success'
            )
        );
    }

    public function postDiscussionComment(Request $request){
        $user = $request->input('user');
        $discussion = $request->input('discussion');
        $comment = $request->input('comment');
        $date = Carbon::now();

        $id = DB::table('discussion_comment')
        ->insertGetId([
            'discussion_id'=>$discussion,
            'user_id'=>$user,
            'comment'=>$comment,
            'date'=>$date
        ]);

        if($id){
            $comment = DB::table('discussion_comment')
            ->join('users','users.id','=','discussion_comment.user_id')
            ->select('users.id as user','users.name','users.image','discussion_comment.comment','discussion_comment.id')
            ->where('discussion_comment.id','=',$id)->first();
            $total_comments = DB::table('discussion_comment')
            ->where('discussion_id','=',$discussion)->get()->count();
            event(new discussionComment($total_comments,$discussion)); // socket trigger
            return json_encode(
                array(
                    'status'=>'success',
                    'comment'=>$comment
                )
                );
        }

    }

    public function DiscussionComments($discussion){
        $comments = DB::table('discussion_comment')
        ->join('users','users.id','=','discussion_comment.user_id')
        ->select('users.id as user','users.name' ,'users.image','discussion_comment.comment','discussion_comment.id')
        ->where('discussion_id','=',$discussion)
        ->orderBy('discussion_comment.id' ,'desc')
        ->simplePaginate(10);
        
        return json_encode(
            array(
                'status'=>'success',
                'comments'=>$comments
            )
            );
    }

    public function DeleteDiscussionComments($commentId){
        DB::table('discussion_comment')
        ->where('id','=',$commentId)->delete();
        return json_encode(
            array('status'=>'success')
        );
    }

    public function DiscussionSuggestion($userId){
        $discussions = DB::table('discussion')
        ->join('club','discussion.club_id','=','club.id')
        ->where('discussion.status','=','open')
        ->select('discussion.id','discussion.topic','discussion.status','discussion.date','club.name as club')
        ->inRandomOrder()
        ->simplePaginate(10);
        foreach($discussions as $key=>$discussion){
            $discussions[$key]->time = $this->dateCalculator($discussion->date);
        }

     return json_encode(
         array('status'=>'success',
         'discussions'=>$discussions
         )
     );
    }

    public function DiscussionSearch($word){
        $search = Discussion::search($word)->query(function($query) { 
            $query->select(['id','topic']);  
     })->simplePaginate(20);
        return json_encode(
            ['status'=>'success','discussions'=>$search]
        );
    }
}
