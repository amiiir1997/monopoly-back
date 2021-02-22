<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Game;
use App\Player;
use App\Events\Newmassage;
use App\Card;
use App\Deal;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function placetocard($place){
        $places=[-1,0,-1,1,-1,22,2,-1,3,4,-1,5,26,6,7,23,8,-1,9,10,-1,11,-1,12,13,24,14,15,27,16,-1,17,18,-1,19,25,-1,20,-1,21];
        return $places[$place]; 
    }
    public function prices($cardnum){
        $prices = [60,60,100,100,120,140,140,160,180,180,200,220,220,240,260,260,280,300,300,320,350,400,200,200,200,200,140,150];
        return $prices[$cardnum];
    }
    public function colors($cardnum){
        $colors=[[0,1],[0,1],[2,3,4],[2,3,4],[2,3,4],[5,6,7],[5,6,7],[5,6,7],[8,9,10],[8,9,10],[8,9,10],[11,12,13],[11,12.13],[11,12,13],[14,15,16],[14,15,16],[14,15,16],[17,18,19],[17,18,19],[17,18,19],[20,21],[20,21],[22,23,24,25],[22,23,24,25],[22,23,24,25],[22,23,24,25],[26,27],[26,27]];
        return $colors[$cardnum];
    }
    public function rents($cardnum,$level){
        $rents = [[2,4,6,6,8,10,10,12,14,14,16,18,18,20,22,22,24,26,26,28,35,50,25,25,25,25]
            ,[10,20,30,30,40,50,50,60,70,70,80,90,90,100,110,110,120,130,130,150,175,200,50,50,50,50]
            ,[30,60,90,90,100,150,150,180,200,200,220,250,250,300,330,330,360,390,390,450,500,600,100,100,100,100]
            ,[90,180,270,270,300,450,450,500,550,550,600,700,700,750,800,800,850,900,900,1000,1100,1400,200,200,200,200]
            ,[160,320,400,400,450,625,625,700,700,700,800,875,875,925,975,975,1025,1100,1100,1200,1300,1700]
            ,[250,450,550,550,600,750,750,900,900,950,1000,1050,1050,1100,1150,1150,1200,1275,1275,1400,1500,2000]];
        return $rents[$level][$cardnum];
    }
    public function creategame(Request $request){
    	$player = new Player;
    	$player->playercode = Str::random(20);
    	$player->money = 0;
    	$player->name = $request->input('name');
    	$player->gamenumber = 0;
    	$player->save();
    	$game = new Game;
    	$gamecode=Str::random(6);;
    	$game->gamecode=$gamecode;
    	$game->creater=$player->id;
    	$game->startingmoney=$request->input('startingmoney');
    	$game->created_at=now();
    	$game->save();
    	$player->gameid=$game->id;
    	$player->save();
    	$res ['playercode']=$player->playercode;
        $res ['gamecode']=$game->gamecode;
    	return $res;
    }
    public function joindata($gameid){
        $players = Player::where('gameid' , $gameid)->orderby('gamenumber')->get();
        for($i=0 ; $i <count($players) ;$i++){
            $playersdata[$i]['name']=$players[$i]['name'];
            $playersdata[$i]['id']=$players[$i]['gamenumber'];
            }
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'a.'.json_encode($playersdata)));
        }
    }
    public function enterws(Request $request){
        if(! $creater = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        event(new Newmassage($request->input('playercode'),'test'));
    }
    public function joingame(Request $request){
    	$game = Game::where('gamecode' , $request->input('gamecode'))->where('start_at' , NULL)->first();
    	if (is_null($game))
    	{
    		return response('This Game is Not Available', 450);
    	}
    	$player = new Player;
    	$player->playercode = Str::random(20);
    	$player->money = 0;
    	$player->name = $request->input('name');
    	$player->gamenumber = 0;
    	$player->gameid=$game['id'];
    	$player->save();
    	$res['gamecode']=$request->input('gamecode');
        $res['playercode']=$player->playercode;
    	return $res;
    }
    public function finduser($playercode){
        $player = Player::where('playercode' , $playercode)->first();
        if(is_null($player))
            return 0;
        return $player;
    }
    public function startgame(Request $request){
        if(!!! $creater = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        $game = Game::where('creater' , $creater['id'])->whereNull('start_at')->first();
        if(is_null($game))
            return response('This Game is Not Available', 452);
        $playersid = Player::where('gameid' , $game['id'])->orderby('playercode')->select('id')->get();
        Game::where('creater' , $creater['id'])->update(['start_at' => now() , 'playerscount' => count($playersid)]);
        for($i=0;$i<count($playersid);$i++){
            Player::where('id' , $playersid[$i]['id'])->update(['gamenumber' => $i , 'money' => $game['startingmoney']]);
        }
        //$this->startalert($game['id']);
        return 0;
    }
    public function initialdata(Request $request){
        if(!!! $player = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        $game = Game::where('id' , $player['gameid'])->first();
        if (is_null($game))
        {
            return response('This Game is Not Available', 450);
        }
        if ($game['start_at'] == null){
            $this->joindata($player['gameid']);
            $res['creater']=false;
            if($game['creater']== $player['id'])
                $res['creater']=true;
            $res['join']=1;
            $res['gamecode']=$game['gamecode'];
            $res ['gamenumber'] = $player['gamenumber'];
            return $res;
        }
        $this->playersdata($player['gameid']);
        $this->playerscard($player['gameid']);
        $this->playersstep($player['gameid']);
        $this->playersturn($player['gameid']);
        $this->move($player['gameid']);
        $res['join']=0;
        $res ['gamenumber'] = $player['gamenumber'];
        return $res;        
    }
    public function sendmassage(Request $request){
        if(!!! $player = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        $players = Player::where('gameid' , $player['gameid'])->get();
        $massage[]=[
            'pm' => $request->input('text'),
            'name' => $player['name'],
        ];
        for($i=0 ; $i<count($players) ; $i++){
            $massage[0]['mypm'] = $players[$i]['id'] == $player['id'] ? '1' : '0';
            event(new Newmassage($players[$i]['playercode'],'9.'.json_encode($massage)));
        }
    }
    public function playersdata ($gameid){
        $players = Player::where('gameid' , $gameid)->orderby('gamenumber')->get();
        for($i=0 ; $i <count($players) ;$i++){
            $playersdata[$i]['gamenumber']=$players[$i]['gamenumber'];
            $playersdata[$i]['place']=$players[$i]['place'];
            $playersdata[$i]['name']=$players[$i]['name'];
            $playersdata[$i]['money']=$players[$i]['money'];
            $playersdata[$i]['passleft']=$players[$i]['passleft'];
        }
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'8.'.json_encode($playersdata)));
        }
    }
    public function playerscard($gameid){
        $playerscard =Card::where('gameid' , $gameid)->get();
        $players = Player::where('gameid' , $gameid)->get();
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'7.'.json_encode($playerscard)));
        }
    }
    public function playersdice($gameid){
        $game = Game::where('id' , $gameid)->first();
        if(is_null($game))
            return response('This Game is Not Available', 452);
        $dice[]=[
            'dice1' => $game['dice1'],
            'dice2' => $game['dice2'],
        ];
        $players = Player::where('gameid' , $gameid)->get();
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'6.'.json_encode($dice)));
        }
    }
    public function playersstep($gameid){
        $game = Game::where('id' , $gameid)->first();
        if(is_null($game))
            return response('This Game is Not Available', 452);
        $step=$game['step'];
        $players = Player::where('gameid' , $gameid)->get();
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'3.'.$step));
        }
    }
    public function playersturn($gameid){
        $game = Game::where('id' , $gameid)->first();
        if(is_null($game))
            return response('This Game is Not Available', 452);
        $turn=$game['turn'];
        $players = Player::where('gameid' , $gameid)->get();
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'5.'.$turn));
        }
    }
    public function roll(Request $request){
        if(!!! $player = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        $game = Game::where('turn' ,$player['gamenumber'])->where('id',$player['gameid'])->first();
        if(is_null($game))
            return response('Not Your Turn', 453);
        if($game['step'] != 0)
            return response('Do Move Needed', 456);
        $dice1 = rand(1,6);
        $dice2 = rand(1,6);
        Game::where('turn' ,$player['gamenumber'])->where('id',$player['gameid'])->update(['dice1' => $dice1 , 'dice2' => $dice2 ,'step' => 1]);
        if($player['place'] + $dice1 + $dice2 > 39){
            if( $player['passleft'] > 0){
                Player::where('playercode' , $request->input('playercode'))->update(['place' => $player['place'] + $dice1 + $dice2 -40 , 'passleft' => $player['passleft'] - 1, 'money' => $player['money'] + 200]);
            }
            else{
                Player::where('playercode' , $request->input('playercode'))->update(['place' => $player['place'] + $dice1 + $dice2 -40]);
            }
        }
        else{
            Player::where('playercode' , $request->input('playercode'))->update(['place' => $player['place'] + $dice1 + $dice2]);
        }
        $this->playersdice($player['gameid']);
        $this->playersdata($player['gameid']);
        $this->playersstep($player['gameid']);
        $this->move($player['gameid']);
    }
    public function move($gameid){
        $game = Game::where('id' , $gameid)->first();
        if (is_null($game))
        {
            return response('This Game is Not Available', 450);
        }
        if($game['movedone'] == 1){
            return response('Roll Needed', 454);
        }
        $player= Player::where('gameid' , $gameid)->where('gamenumber' , $game['turn'])->first();
        if($player['place'] == 2 ||$player['place'] == 17 || $player['place'] == 33 ){
            $move[]=[
                'playernum' => $game['turn'],
                'id' => 8
            ];
            $players = Player::where('gameid' , $gameid)->get();
            for($i=0 ; $i<count($players) ; $i++){
                event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
            }
            return 1; 
        }
        elseif( $player['place'] == 7 || $player['place'] == 22 ||$player['place'] == 36 ){
            $move[]=[
                'playernum' => $game['turn'],
                'id' => 9
            ];
            $players = Player::where('gameid' , $gameid)->get();
            for($i=0 ; $i<count($players) ; $i++){
                event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
            }
            return 1; 
        }
        elseif($player['place'] == 4){
            $move[]=[
                'playernum' => $game['turn'],
                'tax' => 100,
                'id' => 4
                ];
            $players = Player::where('gameid' , $gameid)->get();
                for($i=0 ; $i<count($players) ; $i++){
                    event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                }
            return 1;
        }
        elseif($player['place'] == 38){
            $move[]=[
                'playernum' => $game['turn'],
                'tax' => 200,
                'id' => 4
                ];
            $players = Player::where('gameid' , $gameid)->get();
                for($i=0 ; $i<count($players) ; $i++){
                    event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                }
            return 1;
        }
        elseif($player['place'] == 10){
            $move[]=[
                'playernum' => $game['turn'],
                'id' => 6
            ];
            $players = Player::where('gameid' , $gameid)->get();
            for($i=0 ; $i<count($players) ; $i++){
                event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
            }
            return 1;        }
        elseif($player['place'] == 30){
            $move[]=[
                'playernum' => $game['turn'],
                'id' => 5
            ];
            $players = Player::where('gameid' , $gameid)->get();
            for($i=0 ; $i<count($players) ; $i++){
                event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
            }
            return 1;
        }
        elseif($player['place'] == 20 || $player['place'] == 0){
            $move[]=[
                'playernum' => $game['turn'],
                'id' => 7
            ];
            $players = Player::where('gameid' , $gameid)->get();
            for($i=0 ; $i<count($players) ; $i++){
                event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
            }
            return 1;
        }
        elseif($player['place'] == 12 || $player['place'] == 28){
            $cardnum=$this->placetocard($player['place']);
            $card = Card::where('gameid' , $gameid)->where('cardnum' , $cardnum)->first();
            if (is_null($card))
            {
                $move[]=[
                    'cardnum' => $cardnum,
                    'playernum' => $game['turn'],
                    'id' => 1
                ];
                $players = Player::where('gameid' , $gameid)->get();
                    for($i=0 ; $i<count($players) ; $i++){
                        event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                    }
                return 1;
            }
            else{
                $move[]=[
                    'cardnum' => $cardnum,
                    'level' => $card['level'],
                    'owner' => $card['ownernum'],
                    'playernum' => $game['turn'],
                    'dices' => $game['dice1']+$game['dice2'],
                    'id' => 3

                ];
                $players = Player::where('gameid' , $gameid)->get();
                    for($i=0 ; $i<count($players) ; $i++){
                        event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                    }
                return 1;
            }
        }
        else{
            $cardnum=$this->placetocard($player['place']);
            $card = Card::where('gameid' , $gameid)->where('cardnum' , $cardnum)->first();
            if (is_null($card))
            {
                $move[]=[
                    'cardnum' => $cardnum,
                    'playernum' => $game['turn'],
                    'id' => 1
                ];
                $players = Player::where('gameid' , $gameid)->get();
                    for($i=0 ; $i<count($players) ; $i++){
                        event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                    }
                return 1;
            }
            else{
                $move[]=[
                    'cardnum' => $cardnum,
                    'level' => $card['level'],
                    'owner' => $card['ownernum'],
                    'playernum' => $game['turn'],
                    'id' => 2

                ];
                $players = Player::where('gameid' , $gameid)->get();
                    for($i=0 ; $i<count($players) ; $i++){
                        event(new Newmassage($players[$i]['playercode'],'4.'.json_encode($move)));
                    }
                return 1;
            }
        }
    }
    public function domove(Request $request){
        if(!!! $player = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        $game = Game::where('id' , $player['gameid'])->where('turn' , $player['gamenumber'])->first();
        if (is_null($game))
        {
            return response('Not Your Turn', 453);
        }
        if($game['movedone'] == 1){
            return response('Roll Needed', 454);
        }
        if($player['place'] == 2 ||$player['place'] == 17 || $player['place'] == 33 ){
            //box;
        }
        elseif( $player['place'] == 7 || $player['place'] == 22 ||$player['place'] == 36 ){
            //chance;
        }
        elseif($player['place'] == 4){
            if($player['money'] < 100)
            {
                return response('Not Enough Money', 455);
            }
            Player::where('id' , $player['id'])->update(['money' => $player['money']-100]);
            $this->playersdata($player['gameid']);
        }
        elseif($player['place'] == 38){
            if($player['money'] < 200)
            {
                return response('Not Enough Money', 455);
            }
            Player::where('id' , $player['id'])->update(['money' => $player['money']-100]);
            $this->playersdata($player['gameid']);
        }
        elseif($player['place'] == 10){
            // noting
        }
        elseif($player['place'] == 30){
            if($request->input('buy') == 1){
                if($player['money'] < 50)
                {
                    return response('Not Enough Money', 455);
                }
                Player::where('id' , $player['id'])->update(['jail' => 0 , 'money' => $player['money']-50]);
                $this->playersdata($player['gameid']);
            }
            else{
                if ($player['jail'] == 0) {
                    Player::where('id' , $player['id'])->update(['jail' => 3]);
                }
            }
            $this->playersdata($player['gameid']);
        }
        elseif ($player['place'] == 20 || $player['place'] == 0){
            //nothing needed
        }
        elseif($player['place'] == 12 || $player['place'] == 28){
            $cardnum=$this->placetocard($player['place']);
            $card = Card::where('gameid' , $player['gameid'])->where('cardnum' , $cardnum)->first();
            if (is_null($card))
            {
                if($request->input('buy') == 1){
                    if($player['money'] < $this->prices($cardnum))
                    {
                        return response('Not Enough Money', 455);
                    }
                    else{
                        Player::where('id' , $player['id'])->update(['money' => $player['money']-$this->prices($cardnum)]);
                        $this->buyfrombank($player,$cardnum,0);
                        $this->playersdata($player['gameid']);
                        $this->playerscard($player['gameid']);
                    }
                }
                else{
                    //nothing needed
                }
            }
            else{
                if($card['level']==0)
                    $rent = 4 * ($game['dice1']+$game['dice2']);
                else
                    $rent = 10 * ($game['dice1']+$game['dice2']);
                if($player['money'] < $rent)
                {
                    return response('Not Enough Money', 455);
                }
                else{
                    Player::where('id' , $player['id'])->update(['money' => $rent]);
                    $temp=Player::where('gameid', $game['id'])->where('gamenumber' , $card['ownernum'])->first();
                    Player::where('gameid', $game['id'])->where('gamenumber' , $card['ownernum'])->update(['money' => $temp['money']+$rent]);
                    $this->playersdata($player['gameid']);
                }
            }
        }
        else{
            $cardnum=$this->placetocard($player['place']);
            $card = Card::where('gameid' , $player['gameid'])->where('cardnum' , $cardnum)->first();
            if (is_null($card))
            {
                if($request->input('buy') == 1){
                    if($player['money'] < $this->prices($cardnum))
                    {
                        return response('Not Enough Money', 455);
                    }
                    else{
                        Player::where('id' , $player['id'])->update(['money' => $player['money']-$this->prices($cardnum)]);
                        $this->buyfrombank($player,$cardnum,0);
                        $this->playersdata($player['gameid']);
                        $this->playerscard($player['gameid']);
                    }
                }
                else{
                    //nothing needed
                }
            }
            else{
                $rent = $this->rents($cardnum,$card['level']);
                if($player['money'] < $rent)
                {
                    return response('Not Enough Money', 455);
                }
                else{
                    Player::where('id' , $player['id'])->update(['money' => $player['money']-$rent]);
                    $temp=Player::where('gameid', $game['id'])->where('gamenumber' , $card['ownernum'])->first();
                    Player::where('gameid', $game['id'])->where('gamenumber' , $card['ownernum'])->update(['money' => $temp['money']+$rent]);
                    $this->playersdata($player['gameid']);
                }
            }
        }
        $temp = $game['turn'] + 1;
        if($temp == $game['playerscount'])
            $temp = 0 ;
        Game::where('id' , $player['gameid'])->update(['step' => 0 , 'turn' => $temp]);
        $this->playersstep($player['gameid']);
        $this->playersturn($player['gameid']);
    }
    public function buyfrombank($player,$cardnum,$level){
        $card = new Card;
        $card->ownernum = $player['gamenumber'];
        $card->cardnum = $cardnum;
        $card->gameid = $player['gameid'];
        $card->level = $level;
        $card->save();
        if($cardnum==5 ||$cardnum==15 || $cardnum==25 ||$cardnum==35){
            $cards= Card::where('gameid',$player['gameid'])->where('ownernum',$player['gamenumber'])->whereIn('cardnum' , [5,15,25,35])->count();
            Card::where('gameid',$player['gameid'])->where('ownernum',$player['gamenumber'])->whereIn('cardnum' , [5,15,25,35])->update(['level' => $cards-1]);
            return 1;
        }
        $colors=$this->colors($cardnum);
        $cards= Card::where('gameid',$player['gameid'])->where('ownernum',$player['gamenumber'])->whereIn('cardnum' , $colors)->count();
        if($cards==count($colors)){
            Card::where('gameid',$player['gameid'])->where('ownernum',$player['gamenumber'])->whereIn('cardnum' , $colors)->update(['level' => 1]);
        }
        return 1;
    }
    public function dealsuggest(Request $request){
        if(!!! $player = $this->finduser($request->input('playercode'))){
            return response('Player Not Exist', 451);
        }
        Deal::where('gameid' ,$player['gameid'])->where('time' , '<=' ,now())->delete();
        $deal = Deal::where('gameid' ,$player['gameid'])->first();
        if(! is_null($deal)){
             return response('One Deal In Process', 457);
        }
        $dealmoneycheck = Player::where('gameid' , $player['gameid'])->where('gamenumber' , $request->input('dealnum'))->first();
        if(is_null($dealmoneycheck)){
            return response('Player Not Exist', 451);
        }
        $dealcards = [];
        $suggestcards = [] ;
        $dealcardsinput = $request->input('dealcards');
        $suggestcardsinput = $request->input('playercards') ;
        for($i=0;$i<28;$i++){
            if($dealcardsinput[$i] == 1)
                array_push($dealcards, $i);
            if($suggestcardsinput[$i] == 1)
                array_push($suggestcards, $i);
        }
        $dealcheck = Card::where('gameid' , $player['gameid'])->where('ownernum' , $request->input('dealnum'))->wherein('cardnum' , $dealcards)->count();
        $suggestcheck = Card::where('gameid' , $player['gameid'])->where('ownernum' , $player['gamenumber'])->wherein('cardnum' , $suggestcards)->count();
        if($dealcheck != count($dealcards) || $suggestcheck != count($suggestcards) || $request->input('playermoney') > $player['money'] || $dealmoneycheck['money'] < $request->input('dealmoney')){
            return response('Wrong Cards Or Money', 458);
        }
        $deal = new Deal;
        $deal->gameid=$player['gameid'];
        $deal->suggestnum=$player['gamenumber'];
        $deal->dealnum=$request->input('dealnum');
        $deal->suggestmoney=$request->input('playermoney');
        $deal->dealmoney=$request->input('dealmoney');
        $deal->suggestcards=json_encode($dealcards);
        $deal->dealcards=json_encode($suggestcards);
        $deal->time=now()->addSeconds(20);
        $deal->save();
        $this->suggest($player['gameid']);
    }
    public function suggest($gameid){
        $deal = Deal::where('gameid' , $gameid)->where('time' ,'>=', now())->first();
        if(is_null($deal))
            return response('This Game No Deal In process', 459);
        $players = Player::where('gameid' , $gameid)->get();
        for($i=0 ; $i<count($players) ; $i++){
            event(new Newmassage($players[$i]['playercode'],'2.'.json_encode($deal)));
        }
    }
}