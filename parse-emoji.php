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

	var parseRules = [{
		reg:/:[^:]+:/g,
		parse:(function(){
			var cache = JSON.parse(localStorage['EMOJI_DATA']||"{}").emoji;
			return function(emoji){
				return cache[emoji.replace(/:/g,'')];
			}
		})()
	},{
		reg:/\[[^\]]+\]/g,
		parse:function(emoji){
			return false;
		}
	}];

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

	var load = function(callBack){
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

	var parseEmoji = function(rootNode , opt){
		var textNodes = getTextNode(rootNode);
		var url , documentFragment , node , parseNodes , parseNode , parseString ,	 unicode , i , j ;
		for( i = 0 , node ; node = textNodes[i] ; i++ ){
			parseString = node.nodeValue.replace(opt.reg,function(emojiString){
				 url = opt.parse(emojiString) ;
				 if(!url)return emojiString;
				 return "<img class='wp-emoji' src='" + url + "' title='" + emojiString + "' alt='" + emojiString + "' draggable=false />";
			});

			if(parseString != node.nodeValue){
				console.log(parseString,node.nodeValue);
				node.parentNode.replaceChild(textToNodes(parseString),node);
			}
		}
	}

	parseRules.forEach(function(opt){
		parseEmoji(document.querySelector("body"),opt);
	});

})(this,document,jQuery);
</script>
<?php
});