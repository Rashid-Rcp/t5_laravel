<?php

namespace App\Http\Controllers;

use DateTime;
use GrahamCampbell\ResultType\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClubController extends Controller
{
    //
    public function userClub($userId){
        //echo json_encode($request);
       $result = array(
           'status'=>'not_found'
       );
       $club =  DB::table('club_members')
          ->where('member_id',$userId)
          ->where('role','admin')
          ->select('club_id')
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

        if ($request->hasFile('image')) {
            $imageName = time().uniqid().'.'.$request->file('image')->extension();  
            $path = public_path('images/club/'.$imageName); 
      
            while(file_exists($path)){
              $imageName = time().uniqid().'.'.$request->file('image')->extension();
              $path = public_path('images/club/'.$imageName); 
            }
            $request->file('image')->move(public_path('images/club'), $imageName);
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

    public function getMembers($clubName){
        $clubId = DB::table('club')
        ->where('name',$clubName)
        ->pluck('id')->toArray()[0];
        $members = DB::table('users')
        ->whereIn('id', function($query) use($clubId){
            $query->select('member_id')
            ->from('club_members')
            ->where('club_id', $clubId)
            ->where(function($query){
               $query-> where('role', 'admin')
                ->orWhere('role', 'participant');
            });
        })
        ->select('id','name','phone','image')
        ->get();
        
        if (count($members) >= 2) { 
            return json_encode(array(
                'status'=>'success',
                'members'=>$members
            ));
        }
        else{
            return json_encode(array(
                'status'=>'no_members',
            ));
        }
    }

    public function getUserClubs($userId){
        $clubs = DB::table('club')
        ->whereIn('id',function($query) use($userId){
            $query->select('club_id')
            ->from('club_members')
            ->where('member_id',$userId)
            ->where('role','admin');
        })
        ->pluck('name');

        if($clubs){
           return json_encode(
               array(
                   'status'=>'success',
                   'clubs'=>$clubs
               )
           ) ;
        }
        else{
            return json_encode(
                array(
                    'status'=>'error',
                )
            ) ;
        }
    }

    public function clubDetails($clubId){

        $members = DB::table('club_members')
        ->select('club_id',DB::raw('COUNT(id) AS members'))
        ->groupBy('club_id');

        $discussions = DB::table('discussion')
        ->select('club_id',DB::raw('COUNT(id) AS discussions'))
        ->groupBy('club_id');

        $clubDetails = DB::table('club')
        ->leftJoinSub($members,'members',function($join){
            $join->on('members.club_id','=','club.id');
          })
        ->leftJoinSub($discussions,'discussions',function($join){
            $join->on('discussions.club_id','=','club.id');
          })
        ->select('club.name', 'club.image','club.description','members.members','discussions.discussions')
        ->where('club.id','=',$clubId)
        ->first();

        //latest 
        $votes = DB::table('discussion_vote')
        ->select('discussion_id',DB::raw('COUNT(id) AS votes'))
        ->groupBy('discussion_id');

        $comments = DB::table('discussion_comment')
        ->select('discussion_id',DB::raw('COUNT(id) AS comments'))
        ->groupBy('discussion_id');

        $answers = DB::table('discussion_answer')
        ->select('discussion_id',DB::raw('COUNT(id) AS answers'))
        ->groupBy('discussion_id');

        $latest_discussion = DB::table('discussion')
        ->leftJoinSub($votes,'votes',function($join){
            $join->on('votes.discussion_id','=','discussion.id');
          })
          ->leftJoinSub($answers,'answers',function($join){
            $join->on('answers.discussion_id','=','discussion.id');
          })
          ->leftJoinSub($comments,'comments',function($join){
            $join->on('comments.discussion_id','=','discussion.id');
          })
        ->where('discussion.club_id','=',$clubId)
        ->select('discussion.id','discussion.topic','discussion.vote','discussion.comment','discussion.status','discussion.date','votes.votes','comments.comments','answers.answers')
        ->orderBy('discussion.date' ,'desc')
        ->first();
        if($latest_discussion){
            $latest_discussion->{'time'} =$this->dateCalculator($latest_discussion->date);
        }
        
        //members
        $members = DB::table('club_members')
        ->join('users','users.id','=','club_members.member_id')
        ->where('club_members.club_id','=',$clubId)
        ->select('users.id','users.name','users.image','club_members.role')
        ->simplePaginate(10);

        return json_encode(
            [
                'status'=>'success',
                'clubDetails'=>$clubDetails,
                'latest'=>$latest_discussion,
                'members'=>$members,
            ]
            );
    }

    public function clubDiscussions($clubId){
       
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
          
        
        ->where('discussion.club_id',$clubId)
        ->select('discussion.id','discussion.topic','discussion.vote','discussion.comment','discussion.status','discussion.date','votes.votes','comments.comments','answers.answers','club.name as club')
        ->orderBy('discussion.date' ,'desc')
        ->simplePaginate(10);
       
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

    public function clubList($type, $user){
        $members = DB::table('club_members')
        ->select('club_id',DB::raw('count(id) as members'))
        ->groupBy('club_id');

        $clubs = DB::table('club_members')
        ->join('club','club.id','=','club_members.club_id')
        ->leftJoinSub($members,'members',function($join){
            $join->on('members.club_id','=','club_members.club_id');
          })
        ->where('club_members.member_id','=',$user)
        ->where(function($query) use($type){
            if($type === 'admin'){
                $query->where('club_members.role','=','admin');
            }
            else{
                $query->where('club_members.role','!=','admin');
            }
        })
        ->select('club.id','club.image','club.name','members.members')
        ->simplePaginate(20);
        return json_encode([
            'status'=>'success',
            'clubs'=>$clubs
        ]);
    }

    public function clubSearch($word){
        $word = ltrim($word, '@');
        $club1 = DB::table('club')
        ->where('name','=',$word)
        ->select('id','name')->get()->toArray();
        $club2 = DB::table('club')
        ->where('name','like',$word.'%')
        ->select('id','name')
        // ->union($club1)
        ->limit(20)->get()->toArray();
        if(count($club1)){
            $id = $club1[0]->id;
            $remove = -1;
            foreach($club2 as $index => $value){
                if($value->id === $id){
                    $remove = $index;
                    break;
                }
            }
            if($remove !== -1){
                unset($club2[$remove]);
            }
            array_unshift($club2,$club1[0]);
        }
        return json_encode([
            'status'=>'success',
            'clubs'=>$club2
        ]);
    }
}
