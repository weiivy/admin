// 扩展函数
var popup = {}
popup.alert = function (title, content, callback) {
	var p = $.popup({
		title: title,
		content: content,
		modal: true,
		className: "popup-css-alert",
		button: [
			{
				"text": "确认",
				"action": function (event, popup, button, index) {
					popup.remove();
					callback && callback();
				}}
		]
	})
	p.show();
	return p;
}

popup.confirm = function (title, content, yesCallback, noCallback) {
	var p = $.popup({
		title: title,
		content: content,
		modal: true,
		className: "popup-css-confirm",
		button: [
			{
				"text": "确认",
				"action": function (event, popup, button, index) {
					yesCallback && yesCallback();
					popup.remove();
				}
			},
			{
				"text": "取消",
				"action": function (event, popup, button, index) {
					popup.remove();
					noCallback && noCallback();
				}
			}
		]
	})
	p.show();
	return p;
}

popup.cute = function (content, timeout) {
	var p = $.popup({
		content: content,
		modal: false,
		className: "popup-css-cute"
	})
	p.show();
	if (timeout != 0) {
		timeout = timeout || 3000
		setTimeout(function () {
			p.remove();
		}, timeout);
	}
	return p;
}

popup.modalCute = function (content, timeout, callback) {
	var p = $.popup({
		content: content,
		modal: true,
		className: "popup-css-modal-cute"
	})
	p.show();
	if (timeout != 0) {
		timeout = timeout || 3000
		setTimeout(function () {
			p.remove();
			callback && callback();
		}, timeout);
	}
	return p;
}

popup.loadingPopup = null;
popup.loadingStart = function () {
	popup.loadingPopup = $.popup({
		className: "popup-css-loading",
		content: "",
		modal: true
	})
	popup.loadingPopup.show();
	return popup.loadingPopup;
}
popup.loadingStop = function () {
	popup.loadingPopup && popup.loadingPopup.remove();
	popup.loadingPopup = null
}


//select > option 选择
popup.select = function (selector, callback) {
	var oList = $("<ul class='popup-select-list'></ul>")
	var oSelect = $(selector);
	oSelect.children().each(function () {
		var value = this.value;
		var text = $(this).html();
		var selected = this.selected;
		var li = $("<li class='item' data-callback='selectCallback'></li>");
		value && li.attr("data-value", value);
		text && li.attr("data-text", text).html(text);
		selected && li.attr("selected", "selected");
		oList.append(li)
	})

	var p = $.popup({
		title: "请选择",
		content: oList,
		className: "popup-css-select",
		callback: {
			selectCallback: function (event, popup) {
				var oLi = $(event.target);
				var value = oLi.data("value");
				popup.remove();
				var oldValue = oSelect.val();
				oSelect.val(value)
				if (oldValue != value) {
					oSelect.trigger("change");
					//同样也可以给其他表单赋值,触发表单change事件
				}
				callback && callback(value);
			}
		}
	});
	p.show();
	return p;
};


// 类名快捷调用 事件委托绑定
$(document).on("click", ".popup-alert", function () {
	var title = $(this).data("popup-title");
	var content = $(this).data("popup-content");
	popup.alert(title, content);
})

$(document).on("click", ".popup-confirm", function (e) {
	var title = $(this).data("popup-title");
	var content = $(this).data("popup-content");
	var href = $(this).attr("href") || $(this).data("href");
	var yesCallback = $(this).data("popup-yes-callback");
	var noCallback = $(this).data("popup-no-callback");
	popup.confirm(title, content, function () {
		href && (window.location.href = href);
		yesCallback && eval(yesCallback);
	}, function () {
		noCallback && eval(noCallback);
	});
	e.stopPropagation();
	e.preventDefault();
})

$(document).on("click", ".popup-cute", function () {
	var title = $(this).data("popup-title");
	var content = $(this).data("popup-content");
	var timeout = parseInt($(this).data("popup-timeout"));
	popup.cute(content, timeout);
})

$(document).on("click", ".popup-modal-cute", function () {
	var title = $(this).data("popup-title");
	var content = $(this).data("popup-content");
	var timeout = parseInt($(this).data("popup-timeout"));
	popup.modalCute(content, timeout);
})

$(document).on("click", ".popup-loading", function () {
	popup.loadingStart();
})
$(document).on("click", ".popup-select", function () {
	var selector = $(this).data("popup-data-selector");

//	console.info($(selector))
//	$(selector).filter($(this)).off().on("change", function () {
//		alert($(this).val())
//	});
	popup.select(selector, function (value) {
		$(selector)[0].value = value//.trigger("change");
	})

})