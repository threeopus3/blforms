$(function(){$(".radio_w_image li").click(function(){$(this).addClass("active")})}),function($){var i={common:{init:function(){},finalize:function(){}},home:{init:function(){},finalize:function(){}},about_us:{init:function(){}}},n={fire:function(n,o,t){var c,e=i;o=void 0===o?"init":o,c=""!==n,c=c&&e[n],c=c&&"function"==typeof e[n][o],c&&e[n][o](t)},loadEvents:function(){n.fire("common"),$.each(document.body.className.replace(/-/g,"_").split(/\s+/),function(i,o){n.fire(o),n.fire(o,"finalize")}),n.fire("common","finalize")}};$(document).ready(n.loadEvents)}(jQuery);