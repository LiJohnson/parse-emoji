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
		public function  __construct(){

		}
	}
}

add_action('wp_footer', function(){
	$emojiData = "//api.github.com/emojis";
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
	var EMOJI_DATA = "<?php echo $emojiData ?>";
	var PASS_NODE = /IFRAME|NOFRAMES|NOSCRIPT|SCRIPT|STYLE/i;
	var NODE_TYPE_ELEMENT = 1;
	var NODE_TYPE_TEXT = 3;
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
			var nodes = [];
			div.innerHTML = text;
			for( var i = 0 , n ; n = div.childNodes[i] ; i++ ){
				nodes.push(n);
			}
			return nodes;
		}
	})();

	var parse = function(opt){
		//opt.url = 'http://6+img.t.sinajs.cn/t4/appstyle/expression/ext/normal/70/88_org.gif';
		//return "<span class='wp-emoji' style='background-image:url("+opt.url+")' ></span>";
		return "<img class='wp-emoji' src='"+opt.url+"' alt="+opt.emoji+" draggable=false />";
	};

	var onerror = function(e){
		e.target.parentNode.replaceChild(document.createTextNode(e.target.alt),e.target);
	}

	var ParseEmoji = function(){};

	ParseEmoji.prototype.load = function(callBack){
		var stor = window.localStorage || {};
		var data = JSON.parse(stor['EMOJI_DATA']||"{}");
		if( data.time && new Date() - data.time < 30*24*60*60*1000  ){
			return callBack.call(this,data.emoji);
		}
		$.get(EMOJI_DATA,function(emoji){
			var data = {time:new Date()*1 , emoji:emoji};
			stor['EMOJI_DATA'] = JSON.stringify(data);	
			callBack.call(this,data.emoji);
		},'json');
	}

	ParseEmoji.prototype.parse = function(node , callBack){
		var textNodes = getTextNode(node);
		var url , documentFragment , node , parseNodes , parseNode , parseString , name , unicode , i , j ;
		this.load(function(emojis){
			for( i = 0 , node ; node = textNodes[i] ; i++ ){
				parseString = node.nodeValue.replace(/:[^:]+:/g,function(emojiString){
					emojiString.replace(/:/g,'');
					name = emojiString.replace(/:/g,'');
					url = emojis[name];
					if(!url)return emojiString;

					unicode = (url.match(/unicode\/.+\./)||[""])[0].replace(/(^unicode\/)|(\.$)/g,'');

					return parse( { emoji:emojiString , name:name , url:url,unicode:unicode } );
				});

				if(parseString != node.nodeValue){
					documentFragment = document.createDocumentFragment();
					parseNodes = textToNodes(parseString);

					for( var j = 0 , parseNode ; parseNode = parseNodes[j] ; j++  ){
						if(parseNode.nodeName == 'IMG'){
							parseNode.onerror = onerror;
						}

						documentFragment.appendChild(parseNode);
					}
					node.parentNode.replaceChild(documentFragment,node);
				}
			}
		});
	}

	var pe = new ParseEmoji();
	pe.parse(doc.body);
	win.PE = ParseEmoji;
})(this,document,jQuery);
</script>
<?php
});