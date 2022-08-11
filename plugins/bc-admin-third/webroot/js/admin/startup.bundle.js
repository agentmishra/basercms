/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */
String.prototype.sprintf=function(){var t=this+"",e=Array.prototype.slice.call(arguments),o=!0;if(-1!==t.indexOf("%s",0)&&(o=!1),1===e.length)return o?t.replace(/%1$s/g,e[0]):t.replace(/%s/g,e[0]);for(var a=0;a<e.length;a++){var r=a+1;t=o?t.replace("%"+r+"$s",e[a]):t.replace("%s",e[a])}return t},$((function(){$(".help").bt&&($(".helptext").css("display","none"),$.bt.options.closeWhenOthersOpen=!0,$(".help").bt({trigger:"click",positions:"top",shadow:!0,shadowOffsetX:1,shadowOffsetY:1,shadowBlur:8,shadowColor:"rgba(101,101,101,.6)",shadowOverlap:!1,noShadowOpts:{strokeStyle:"#999",strokeWidth:1},width:"600px",spikeLength:12,spikeGirth:18,padding:20,cornerRadius:0,strokeWidth:1,strokeStyle:"#656565",fill:"rgba(255, 255, 255, 1.00)",cssStyles:{fontSize:"14px"},showTip:function(t){$(t).fadeIn(200)},hideTip:function(t,e){$(t).animate({opacity:0},100,e)},contentSelector:"$(this).next('.helptext').html()"})),$("a[rel='colorbox']").colorbox&&$("a[rel='colorbox']").colorbox({maxWidth:"60%"}),$("#BtnMenuHelp").click((function(){"none"===$("#Help").css("display")?$("#Help").fadeIn(300):$("#Help").fadeOut(300)})),$("#CloseHelp").click((function(){$("#Help").fadeOut(300)})),$.bcUtil.init({}),$.bcToken.init(),$.bcJwt.init(),$("[data-bca-collapse='collapse']").on({click:function(){var t=$(this).attr("data-bca-target");return"open"==$(t).attr("data-bca-state")?($(t).attr("data-bca-state","").slideUp(),$(this).attr("data-bca-state","").attr("aria-expanded","true")):($(t).attr("data-bca-state","open").slideDown(),$(this).attr("data-bca-state","open").attr("aria-expanded","false")),!1}}),$(".error-message:has(ul)").removeClass("error-message").addClass("error-wrap");var t=$.bcUtil.frontFullUrl;document.queryCommandSupported("copy")?t&&$("#BtnCopyUrl").on({click:function(){var e=$('<textarea style=" opacity:0; width:1px; height:1px; margin:0; padding:0; border-style: none;"/>');return e.text(t),$(this).after(e),e.select(),document.execCommand("copy"),e.remove(),$("#BtnCopyUrl").tooltip("dispose"),$("#BtnCopyUrl").tooltip({title:"コピーしました"}),$("#BtnCopyUrl").tooltip("show"),!1},mouseenter:function(){$("#BtnCopyUrl").tooltip("dispose"),$("#BtnCopyUrl").tooltip({title:"公開URLをコピー"}),$("#BtnCopyUrl").tooltip("show")},mouseleave:function(){$("#BtnCopyUrl").tooltip("hide")}}):$("#BtnCopyUrl").hide()}));
//# sourceMappingURL=startup.bundle.js.map