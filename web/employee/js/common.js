var app = {};

app.datetimeConfig = {
	theme: 'ios7 light',
	mode: 'scroller',
	lang: 'zh',
	display: 'bottom',
	animate: 'none',
	preset: 'datetime',
	dateFormat: 'yy-mm-dd',
//	startYear: (new Date()).getFullYear() - 1,
//	endYear: (new Date()).getFullYear() + 1,
	defaultVlaue: new Date(),
//	minDate: new Date(2012, 3, 10, 9, 22),
//	maxDate: new Date(2017, 7, 30, 15, 44),
//	stepMinute: 5,//每间隔5分钟选择
//	invalid: { daysOfWeek: [0, 6], daysOfMonth: ['5/1', '12/24', '12/25'] },//不可选时间
//	defaultValue:new Date(2013,5,6,6,6),
	onSelect: function (values, scroller) {


		//创建 Date 对象的语法：
		//var myDate=new Date()
		//Date 对象会自动把当前日期和时间保存为其初始值。
		//参数形式有以下５种：
		//
		//new Date("month dd,yyyy hh:mm:ss");
		//new Date("month dd,yyyy");
		//new Date(yyyy,mth,dd,hh,mm,ss);
		//new Date(yyyy,mth,dd);
		//new Date(ms);


	}
}

app.popupList = function (title, list) {

	var content = [];
	var callbacks = {};

	for (var i = 0; i < list.length; i++) {
		content.push('<li data-callback="callback' + i + '">' + list[i].html + '</li>');
		callbacks["callback" + i] = list[i].action;
	}

	var p = $.popup({
		title: title,
		content: '<ul>' + content.join('') + '</ul>',
		className: "wx-popup popup-button-list",
		callback: callbacks
	})
	p.show();
	return p;
}

app.confirm = function (title, content, cancel, sure) {
	var p = $.popup({
		title: title,
		content: content,
		modal: true,
		className: "wx-popup popup-confirm",
		button: [
			{
				"text": "取消",
				"action": function (event, popup, button, index) {
					cancel && cancel(event, popup, button, index);
					popup.remove();
				}
			},
			{
				"text": "确认",
				"action": function (event, popup, button, index) {
					popup.remove(event, popup, button, index);
					sure && sure();
				}
			}
		]
	})
	p.show();
	return p;
}

app.cute = function (content, timeout) {
	var handle = $('<div class="common-widget-popup" style="visibility: hidden">' + content + '</div>');
	$("body").append(handle);
	handle.css({
		left: $(window).width() / 2 - handle.width() / 2,
		top: $(window).height() / 2 - handle.height() / 2,
		visibility: "visible"
	});
	setTimeout(function () {
		handle.remove();
	}, timeout || 3000)
}

app.loadingPopup = null;
app.loadingStart = function () {
	app.loadingPopup = $.popup({
		className: "popup-css-loading",
		content: "",
		modal: true
	})
	app.loadingPopup.show();
	return app.loadingPopup;
}
app.loadingStop = function () {
	app.loadingPopup && app.loadingPopup.remove();
	app.loadingPopup = null
}
