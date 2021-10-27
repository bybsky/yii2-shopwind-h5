function wxshare(params) 
{
	var appId = params.signPackage.appId;
	var timestamp = params.signPackage.timestamp;
	var nonceStr = params.signPackage.nonceStr;
	var signature = params.signPackage.signature;
	
	var title = params.content.title;
	var desc  = params.content.desc;
	var linkUrl  = params.content.linkUrl;
	var imgUrl   = params.content.imgUrl;
	
	if(title == undefined || title == '') {
		title = $(document).attr("title");
	}
	if(linkUrl == undefined || linkUrl == '') {
		linkUrl = location.href;
	}
	
	wx.config({
    debug: false,
    appId: appId,
    timestamp: timestamp,
    nonceStr: nonceStr,
    signature: signature,
    jsApiList: [
      // 所有要调用的 API 都要加到这个列表中
      'checkJsApi',
      //'openLocation',
      //'getLocation',
      'onMenuShareTimeline',
      'onMenuShareAppMessage',
      'updateAppMessageShareData',
      'updateTimelineShareData'
    ]
  });
	wx.ready(function () {
		wx.checkJsApi({
      jsApiList: [
        //'getLocation',
        'onMenuShareTimeline',
        'onMenuShareAppMessage',
        'updateAppMessageShareData',
        'updateTimelineShareData'
      ],
      success: function (res) {
        //layer.open({content:JSON.stringify(res)});
      }
    });
		
    // 微信分享给好友，旧接口即将废弃
		wx.onMenuShareAppMessage({
      title: title,
      desc: desc,
      link: linkUrl,
      imgUrl: imgUrl,
      trigger: function (res) {
        // 不要尝试在trigger中使用ajax异步请求修改本次分享的内容，因为客户端分享操作是一个同步操作，这时候使用ajax的回包会还没有返回
        //alert('用户点击发送给朋友');
      },
      success: function (res) {
        layer.open({content:'已分享',time:3});
      },
      cancel: function (res) {
        //alert('已取消');
      },
      fail: function (res) {
        layer.open({content:JSON.stringify(res),time:3});
      }
    });
    
    // 微信分享给好友新接口
    wx.updateAppMessageShareData({ 
        title: title, // 分享标题
        desc: desc, // 分享描述
        link: linkUrl, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
        imgUrl: imgUrl, // 分享图标
        success: function () {
          // 设置成功
        }
    });

    // 微信分享到朋友圈，旧接口即将废弃
    wx.onMenuShareTimeline({
      title: title,
      desc: desc,
      link: linkUrl,
      imgUrl: imgUrl,
      trigger: function (res) {
        // 不要尝试在trigger中使用ajax异步请求修改本次分享的内容，因为客户端分享操作是一个同步操作，这时候使用ajax的回包会还没有返回
        //alert('用户点击分享到朋友圈');
      },
      success: function (res) {
        layer.open({content:'已分享',time:3});
      },
      cancel: function (res) {
        //alert('已取消');
      },
      fail: function (res) {
        layer.open({content:JSON.stringify(res),time:3});
      }
    });
    
    // 微信分享到朋友圈 新接口
    wx.updateTimelineShareData({ 
        title: title, // 分享标题
        link: linkUrl, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
        imgUrl: imgUrl, // 分享图标
        success: function () {
            // 设置成功
        }
    });
  });
}