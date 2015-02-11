<?php 
/*
Plugin Name: parse emoji
Plugin URI: http://github.com/LiJohnson/parse-emoji
Description: parse emoji string to image
Author: Li Johnson
Version: 1.0
Author URI: http://github.com/LiJohnson/
*/

if( !class_exists('ParseEmoji') ){

class ParseEmoji{
	private $adf;
	function __construct(){
		add_action('after_wp_tiny_mce',array(&$this,'editor'));
		add_action('admin_menu',array(&$this,'option'));
		add_action('wp_footer',array(&$this,'wpFooter'));
	}

	function editor(){
		?>
		<link rel="stylesheet" href="http://ichord.github.io/At.js/dist/css/jquery.atwho.css" />
		
		<script src="http://ichord.github.io/Caret.js/src/jquery.caret.js" ></script>
		<script src="http://ichord.github.io/At.js/dist/js/jquery.atwho.js" ></script>
		<script>
		(function($){
			<?php $this->loadEmoji(); ?>
			var config = {
				displayTpl:"<li>${name}<img src=${url} width=15 height=15 /></li>",
				startWithSpace: false,
				//limit: 8,
				insertTpl: "${name}"
			};

			tinyMCE.onAddEditor.add(function(mgr, ed) {
				ed.onInit.add(function(ed, l) {
					jQuery(ed.contentDocument.activeElement).atwho($.extend({
						at:'[',
						callbacks: {
						    remoteFilter: function(query, callback) {
						    	loadEmoji('weibo',function(data){
						    		var arr = [];
						    		$.each(data,function(name,url){
						    			arr.push({name:name,url:url});
						    		});
						    		callback(arr);
						    	});  
							}
						}
					},config)).atwho($.extend({
						at:':',
						callbacks: {
						    remoteFilter: function(query, callback) {
						    	loadEmoji('github',function(data){
						    		var arr = [];
						    		$.each(data,function(name,url){
						    			arr.push({name:":"+name+":",url:url});
						    		});
						    		callback(arr);
						    	});  
							}
						}
					},config));
				});
			});			
		})(jQuery);

		</script>
		<?php
	}

	function option(){
		add_options_page('parse emoji', 'parse emoji', 9 , __file__ , function(){
			require __DIR__ . '/option.php';
		});
	}

	function wpFooter(){
			$emojiData = "//api.github.com/emojis";
			$ajaxUrl = admin_url( 'admin-ajax.php' );
		?>
		<style>
			.wp-emoji{
				width: 20px;
				height: 20px;
				background-size: 20px 20px;
				background-repeat: no-repeat;
				display: inline-block;
			}
		</style>
		<script>
		(function(win,doc,$){
			<?php $this->loadEmoji(); ?>
			var AJAX_URL = "<?php echo $ajaxUrl;?>";
			var PASS_NODE = /IFRAME|NOFRAMES|NOSCRIPT|SCRIPT|STYLE/i;
			var NODE_TYPE_ELEMENT = 1;
			var NODE_TYPE_TEXT = 3;

			var parseRules = [
				{
					reg:/:[^:]+:/g,
					name:'github',
					parse:function(emoji,data){
						return data[emoji.replace(/:/g,'')];
					}
				},{
					reg:/\[[^\]]+\]/g,
					name:'weibo',
					parse:function(emoji,data){
						return data[emoji];
					}
				}
			];

			var getTextNode = function(node){
				var nodes = [];
				for( var i = 0  , subNode ; subNode = node.childNodes[i] ; i++ ){
					if( subNode.nodeType == NODE_TYPE_ELEMENT && !PASS_NODE.test(subNode.nodeName) ){
						nodes = nodes.concat(getTextNode(subNode));
					}else if(subNode.nodeType == NODE_TYPE_TEXT){
						nodes.push(subNode);
					}
				}

				return nodes;
			};

			var textToNodes = (function(){
				var div = document.createElement('DIV');
				return function(text){
					var nodes = [] , documentFragment;

					documentFragment = document.createDocumentFragment();

					div.innerHTML = text;
					for( var i = 0 , n ; n = div.childNodes[i] ; i++ ){
						nodes.push(n);
					}

					for( var j = 0 , node ; node = nodes[j] ; j++  ){
						if(node.nodeName == 'IMG'){
							node.onerror = onerror;
						}

						documentFragment.appendChild(node);
					}
					return documentFragment;
				}
			})();

			var onerror = function(e){
				e.target.parentNode.replaceChild(document.createTextNode(e.target.alt),e.target);
			}

			var parseEmoji = function(opt){
				if(opt.disable)return;

				loadEmoji(opt.name , function(emojiData){
					var textNodes , url  , node  , parseString  , i ;
					textNodes = getTextNode(opt.rootNode || document.body);

					for( i = 0 , node ; node = textNodes[i] ; i++ ){
						parseString = node.nodeValue.replace(opt.reg,function(emojiString){
							 url = opt.parse ? opt.parse(emojiString , emojiData) : emojiData[emojiString] ;
							 if(!url)return emojiString;
							 return "<img class='wp-emoji' src='" + url + "' title='" + emojiString + "' alt='" + emojiString + "' draggable=false />";
						});

						if(parseString != node.nodeValue){
							node.parentNode.replaceChild(textToNodes(parseString),node);
						}
					}
				});
			};

			var help = function(){
				var load = function(i,cb,list){
					list = list || [["<ul id=nav >"]];
					if( !parseRules[i] )return list[0].push("</ul>") , cb(list);
					list[0].push("<li><a href='#" + parseRules[i].name + "'>"+parseRules[i].name+"</a></li>");
					loadEmoji(parseRules[i].name,function(data){
						var html = ["<ul id="+parseRules[i].name+" >"];
						$.each(data,function(name,url){
							html.push("<li><img src='"+url+"' draggable=false title="+name+" alt="+name+" /><p>"+name+"</p></li>");
						});
						html.push("</ul>");
						list.push(html);

						load(i+1,cb,list);
					});
				};

				load(0,function(list){
					var html = ["<style>li {    cursor: pointer;    float: left;    display: inline-block;    padding: 0px; margin: 2px; border: 1px ridge;}li img{    width:20px;    height:20px; margin:5px; } #nav {    position: fixed;    top: 0;    left: 0;    margin-top:0px;    background:#fff;}ul{    margin-top:20px;}p{    display:none;}</style>"];
					list.forEach(function(arr){
						html = html.concat(arr);
					});
					//html.push("#1script#2 document.addEventListener('dblclick',function(e){ var s = window.getSelection() , r =  document.createRange();r.selectNode(e.target.parentElement.childNodes[1]);s.addRange(r); console.log(s);})#1/script#2".replace(/#1/g,"<").replace(/#2/g,'>'));
					var url = URL.createObjectURL(new Blob( html , {type:"text/html"} ))
					window.open(url);
					
				});
			};

			doc.addEventListener("click",function(e){
				e.detail >= 3 && help();
			});

			//help();
			parseRules.forEach(function(opt){
				parseEmoji(opt);
			});

		})(this,document,jQuery);
		</script>
		<?php
	}
	function loadEmoji(){
		?>
		var loadEmoji =function(name,callBack){
			if(!name)return callBack({});
			name = "parse_emoji_" + name;
			var stor = window.localStorage || {};
			var data = JSON.parse(stor[name]||"{}");

			if( data.time && new Date() - data.time < 30*24*60*60*1000 ){
				console.log("locad %s from cache " ,name );
				return callBack(data.emoji);
			}

			$.get(AJAX_URL,{action:name},function(emoji){
				console.log("locad %s from network " ,name );
				var data = {time:new Date()*1 , emoji:emoji};
				stor[name] = JSON.stringify(data);	
				callBack(data.emoji);
			},'json');
		};
		<?php
	}
}
new ParseEmoji();
}


add_action('wp_ajax_parse_emoji_weibo'.$e,function(){require 'weibo.json'; wp_die();});
add_action('wp_ajax_nopriv_parse_emoji_weibo'.$e,function(){require 'weibo.json'; wp_die();});
add_action('wp_ajax_parse_emoji_github'.$e,function(){require 'github.json'; wp_die();});
add_action('wp_ajax_nopriv_parse_emoji_github'.$e,function(){require 'github.json'; wp_die();});

