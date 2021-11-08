<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr\FuncCall;

class UserController extends Controller
{
 
    public function createUser(Request $request){
        $userExists = false;
        $imageName = 'no-photo.jpg';;
        if ($request->hasFile('image')) {
            $imageName = time().uniqid().'.'.$request->file('image')->extension();  
            $path = public_path('images/'.$imageName); 
      
            while(file_exists($path)){
              $imageName = time().uniqid().'.'.$request->file('image')->extension();
              $path = public_path('images/'.$imageName); 
            }
            $request->file('image')->move(public_path('images'), $imageName);
        }
        if (DB::table('users')->where('user_name', $request->input('user_name'))->exists()) {
            $userExists = true;
           return json_encode(array(
            'status'=>'user_name_error',
            'message'=>'user name already exist'
           ));
          }
          if (DB::table('users')->where('email', $request->input('email'))->exists()) {
            $userExists = true;
           return json_encode(array(
            'status'=>'email_error',
            'message'=>'email already exist'
           ));
          }
        if(DB::table('users')->where('phone', $request->input('phone'))->exists()){
            $userExists = true;
            return json_encode(array(
                'status'=>'phone_error',
                'message'=>'phone number already exist'
                ));
        }
        if (!$userExists){
            $id =  DB::table('users')->insertGetId([
              'name' => $request->input('name'),
              'user_name' => $request->input('user_name'),
              'email' => $request->input('email'),
              'phone' => $request->input('phone'),
              'about' => $request->input('about'),
              'image' => $imageName,
              'password' => Hash::make($request->input('password')),
            ]);
            if($id){
                return json_encode(array(
                'status'=>'success',
                'user_id'=>$id,
                ));
            }else{
                return json_encode(array(
                'status'=>'error',
                'message'=>'An error occurred please try again later!',
                ));
            }
        }
    }

    public function loginUser(Request $request){
        $user = false;
        $loginType = 'phone';
        if(filter_var($request->input('user'), FILTER_VALIDATE_EMAIL)){
          $loginType = 'email';
        }
        if($loginType==='email'){
         $user =  DB::table('users')
          ->where('email',$request->input('user'))
          ->select('id','password')
          ->first();
        }
        else{
         $user = DB::table('users')
          ->where('phone',$request->input('user'))
          ->select('id','password')
          ->first();
        }
        if($user){
          if(Hash::check($request->input('password'), $user->password)) {
            return json_encode(array(
              'status'=>'success',
              'userId'=>$user->id
            ));
          }
          else{
            return json_encode(array(
              'status'=>'invalid',
            ));
          }
        }
        else{
          return json_encode(array(
            'status'=>'invalid',
          ));
        }
    }

    public function getUsers(Request $request){
     
      $numbers =json_decode($request->input('numbers')); // array
      $clubId = $request->input('clubId');
      $newClub = $request->input('newClub');
      if(!$newClub){
        $members = DB::table('users')
        ->whereIn('id', function($query) use ($clubId){
          $query->select('member_id')
          ->from('club_members')
          ->where('club_id', $clubId);
        })
        ->pluck('phone')->toArray();
       $numbers = array_values(array_diff($numbers,$members));
      }
      $users = DB::table('users')
      ->whereIn('phone',$numbers)
      ->select('id','name','phone','image')
      ->get();
      if(count($users)){
       return json_encode(array(
          'status'=>'success',
          'users'=>$users
        ));
      }
      else{
        return json_encode(array(
          'status'=>'no_users',
        ));
      }
    }

  public function getUserData($userId){

      $userData = DB::table('users')
      ->where('id','=',$userId)
      ->select('id','name','image','phone','email','about')
      ->first();

      $followers = DB::table('club_members')
        ->select('club_id',DB::raw('COUNT(id) AS followers'))
        ->groupBy('club_id');

      $clubs_follow = DB::table('club_members')
      ->join('club','club.id','=','club_members.club_id')
      ->leftJoinSub($followers,'followers',function($join){
        $join->on('followers.club_id','=','club_members.club_id');
      })
      ->where('club_members.member_id','=',$userId)
      ->where('club_members.role','!=','admin')
      ->select('club.id','club.name','club.image','followers.followers')
      ->simplePaginate(5);
      $clubs_admin = DB::table('club_members')
      ->join('club','club.id','=','club_members.club_id')
      ->leftJoinSub($followers,'followers',function($join){
        $join->on('followers.club_id','=','club_members.club_id');
      })
      ->where('club_members.member_id','=',$userId)
      ->where('club_members.role','=','admin')
      ->select('club.id','club.name','club.image','followers.followers')
      ->simplePaginate(5);

      return json_encode(
        array(
          'status'=>'success',
          'userData'=>$userData,
          'clubsAdmin'=>$clubs_admin,
          'clubsFollow'=>$clubs_follow

        )
        );
    }
}
