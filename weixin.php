<?php
/**
  * wechat php test
  */

//define your token
define("TOKEN", "qchzhu");
$wechatObj = new wechatCallbackapiTest();

//for token validation
//$wechatObj->valid();

//create menu
//$wechatObj->createMenu();

//for auto-responding
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";   

				//获取事件类型
				$type=$postObj->MsgType;
				if($type=='event'){
					$event = $postObj->Event;
					if($event=='subscribe'){
						$contentStr= "【新关注】欢迎关注优享生活，我们将提供最新的生活服务信息，为你的生活增添色彩哦";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='unsubscribe'){
						$contentStr= "【取消关注】非常悲伤，你竟然不需要我了，能告诉我那些地方做得不好吗？";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}else if($event=='CLICK'){
						$contentStr= "【点击】点击自定义菜单";
						$msgType = "text";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}
				}else if($type=='location'){
					$label = $postObj->Label;
					$locationX = $postObj->Location_X;
					$locationY = $postObj->Location_Y;
					$msgType = "text";
					$responseTpl="【LOC】%s 【X】%s【Y】%s";
					$contentStr = sprintf($responseTpl,$label,$locationX,$locationY);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else if($type=='text'){
					if(!empty( $keyword )){//here we return an article list
						$msgType = "news";
						$listTpl=" <xml>
									 <ToUserName><![CDATA[%s]]></ToUserName>
									 <FromUserName><![CDATA[%s]]></FromUserName>
									 <CreateTime>%s</CreateTime>
									 <MsgType><![CDATA[%s]]></MsgType>
									 <ArticleCount>%s</ArticleCount>
									 <Articles>%s</Articles>
									 </xml> ";
						$itemTpl = " <item>
									 <Title><![CDATA[%s]]></Title> 
									 <Description><![CDATA[%s]]></Description>
									 <PicUrl><![CDATA[%s]]></PicUrl>
									 <Url><![CDATA[%s]]></Url>
									 </item>";
						$itemList = "";
						$itemCount = 0;		
						if($keyword=='food' || $keyword=='食品'){
							//get coupon list from remote JSON
							$url = "http://124.42.107.200:8080/foodinfos";
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							for($i=0;$i<count($array);$i++){
								if($itemCount>4)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								if(count($object->food)>0){
									$title = "【".implode(' ',$object->food)."】".$object->title.' '.$object->time; 
								}elseif(count($object->tag)>0){
									$title = "【".implode(' ',$object->tag)."】".$object->title.' '.$object->time; 
								}else{
									$title = $object->title.' '.$object->time; 
								}
								$decription = $object->time.' '.$object->title;
								$picUrl = $object->image;//"http://www.zhuqingchun.com/kill.jpg";
								$linkUrl = $object->url;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}						
						}else if($keyword=='campaign' || $keyword=='闪购'){
							//get campaign list from remote JSON
							$url = "http://www.zhuqingchun.com/rest/campaign";
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							for($i=0;$i<count($array);$i++){
								if($itemCount>4)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								$title = $object->campaign_title; 
								$decription = "折扣信息：".$object->campaign_discount;
								$picUrl = $object->campaign_logo;
								$linkUrl = $object->campaign_url;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}						
						}else if($keyword=='coupon' || $keyword=='团购'){
							//get coupon list from remote JSON
							$url = "http://www.zhuqingchun.com/rest/coupon";
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							for($i=0;$i<count($array);$i++){
								if($itemCount>4)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								$title = "【".$object->coupon_city."】".$object->coupon_name; 
								$decription = $object->coupon_title;
								$picUrl = $object->coupon_logo;
								$linkUrl = $object->coupon_url;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}						
						}else{
							//get article list from remote JSON
							$url = "http://www.zhuqingchun.com/rest/article";
							$lines_array = file($url);
							$lines_string = implode('',$lines_array);            
							$json = htmlspecialchars($lines_string,ENT_NOQUOTES);
							$array = json_decode($json);
							for($i=0;$i<count($array);$i++){
								if($itemCount>4)//we only display 4 items for mobile
									break;
								$object = $array[$i]; // The array could contain multiple instances of your content type
								$title = $object->node_title; // title is a field of your content type
								$decription = "这是测试描述文字。【文章标题】".$object->node_title;
								$picUrl = "http://www.zhuqingchun.com/screwed.png";
								//retrieve image from article
								$regex = '/src="([^"]+)"/i';
								$imgstr = ''.$object->Image;
								$matches = array();
								if(preg_match($regex, $imgstr, $matches)){
									$picUrl = $matches[1];//the first one is matched string
								}	
								//end 								
								$linkUrl = "http://www.zhuqingchun.com/node/".$object->nid;
								$itemStr = sprintf($itemTpl,$title,$description,$picUrl,$linkUrl);
								$itemList = $itemList.$itemStr;
								$itemCount ++;
							}
						}
						if($itemCount>0){
							$resultStr = sprintf($listTpl, $fromUsername, $toUsername, $time, $msgType,$itemCount, $itemList);
							echo $resultStr;
						}else{
							$contentStr= '调试中，还没找到与"'.$keyword.'"相关的内容。重新尝试看看？';
							$msgType = "text";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;						
						}
					}else{
						echo "哦~~~写点什么吧？";
					}
				}else if($type=='image'){
					$picURL = $postObj->PicUrl;
					$msgType = "text";
					$responseTpl="【Image URL】%s";
					$contentStr = sprintf($responseTpl,$picURL);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else if($type=='link'){
					$linkURL = $postObj->Url;
					$msgType = "text";
					$responseTpl="【Link URL】%s";
					$contentStr = sprintf($responseTpl,$linkURL);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}else{
					echo "当前还不支持语音、图片、链接等形式哦";
				}

        }else {
        	echo "error";
        	exit;
        }
    }
		
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	private function postCreateMenu($url, $jsonData){
		$ch = curl_init($url) ;
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$result = curl_exec($ch) ;
		curl_close($ch) ;
		return $result;
	}
	
	public function createMenu(){

		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=qchzhu";
		$data = "{
		\"button\":[
		{
		\"type\":\"view\",
		\"name\":\"关于我们\",
		\"url\":\"http://www.zhuqingchun.com\"
		},
		{
		\"type\":\"view\",
		\"name\":\"访问网络\",
		\"url\":\"http://www.baidu.com\"
		},
		{
		\"type\":\"click\",
		\"name\":\"联系方式\",
		\"key\":\"contact_info\"
		}		
		]
		}";
		$this->postCreateMenu($url,$data);	
	}
}

?>