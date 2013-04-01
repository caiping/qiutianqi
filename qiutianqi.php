<?php
//文中所有{@求天气}全部改成你的微博昵称
include_once( ‘config.php’ );
include_once( ‘saetv2.ex.class.php’ );
$c = new SaeTClientV2( WB_AKEY , WB_SKEY , ’2.00hIY3cC2t5cDEcce2Sdcb2d0VqusC’ );//这里改成你的token
$c->set_debug( DEBUG_MODE );
$fensi = $c->followers_by_id( ’2399144883′ , $cursor = 0 , $count = 50);//这里是自动回粉，改成你的微博id
$mem = memcache_init(); //连接sae的mc服务
if($mem==false)
{
echo “MC连接失败！\n”;
exit;
}
$since_id = $mem->get(“id”);//从缓存获取since_id
$since_id2 = $mem->get(“id2″);//从缓存获取since_id
$huoqu = $c->mentions( $page = 1, $count = 50, $since_id, $max_id = 0, $filter_by_author = 0, $filter_by_source = 0, $filter_by_type = 1 );//获取未读的@求天气的微博列表
$pinglun = $c->comments_to_me( $page = 1 , $count = 50, $since_id2, $max_id = 0, $filter_by_author = 0, $filter_by_source = 0);
// var_dump($huoqu['statuses']);
if( !empty($huoqu['statuses']))
{
$sinc_id = $huoqu['statuses']['0']['id'];//本次获取的最新id，下次以此为最新的id
foreach( $huoqu['statuses'] as $item )
{
$id = $item['id'];
$text = $item['text'];
$text = str_replace(“@求天气”, “”, $text);
$text = trim($text);
$comment = tianqi($text);
$c->send_comment( $id, $comment, $comment_ori = 0);
}
}else{
$sinc_id = $since_id;
}
if( !empty($pinglun['comments']))
{
$sinc_id2 = $pinglun['comments']['0']['id'];//本次获取的最新id，下次以此为最新的id
foreach( $pinglun['comments'] as $item )
{
$cid = $item['id'];//cid评论的id
$sid = $item['status']['id'];//sid微博的id
$uid = $item['user']['id'];//uid是用户id
$text = $item['text'];
$text = str_replace(“@求天气”, “”, $text);
$text = trim($text);
$comment = tianqi($text);
$c->reply( $sid, $comment, $cid, $without_mention = 0, $comment_ori = 0 );
$c->follow_by_id( $uid );
}
}else{
$sinc_id2 = $since_id2;
}
$mem->set(“id”,$sinc_id, 0, 600);
$mem->set(“id2″,$sinc_id2, 0, 600);
$mem->close();
// 自动回粉开始
if( is_array($fensi['users']))
{
foreach( $fensi['users'] as $item )
{
$uid = $item['id'];
$c->follow_by_id( $uid );
}
}
// 自动回粉结束
echo ‘ok’;
//查天气开始
function tianqi($city){
$post_data= array();
$post_data['city'] = $city;
$post_data['submit'] = “submit”;
$url=’http://search.weather.com.cn/wap/search.php’;
$o=”";
foreach($post_data as $k=>$v)
{
$o.= “$k=”.urlencode($v).”&”;
}
$post_data=substr($o,0,-1);
$ch= curl_init();
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$result= curl_exec($ch);
curl_close($ch);
$result=explode(‘/’,$result);
$result=explode(‘.’,$result['5']);
$citynum= $result['0'];
$weatherurl= “http://m.weather.com.cn/data/”.$citynum.”.html”;
$weatherjson= file_get_contents($weatherurl);
$weatherarray= json_decode($weatherjson,true);
$weatherinfo= $weatherarray['weatherinfo'];
$contentTpl= “%s%s（%s）天气：%s，%s，%s。穿衣指数：%s。紫外线指数：%s。洗车指数：%s。舒适指数：%s。晨练指数：%s。【感谢您的使用】”;
$contentStr= sprintf($contentTpl,$weatherinfo['city'],$weatherinfo['date_y'],$weatherinfo['week'],$weatherinfo['temp1'],$weatherinfo['weather1'],$weatherinfo['wind1'],$weatherinfo['index_d'],$weatherinfo['index_uv'],$weatherinfo['index_xc'],$weatherinfo['index_co'],$weatherinfo['index_cl'],$weatherinfo['fchh']);
return $contentStr;
}
