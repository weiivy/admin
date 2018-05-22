/**
 * popup.js
 * @author  Xiao Hua <coolr@foxmail.com>
 * @since   1.0
 *
 var settings = {
        close: "remove",// remove | hide | function 关闭popup执行的真正函数,close相当于关闭弹出的函数别名
        title: "", // string | jq对象
        content: "", // string | jq对象
        button: [//[]空数组时没有按钮
            {
                "text": "是",
                "action": function (event, popup, button, index) {
                    popup.close()
                }
            },
            {
                "text": "否",
                "action": function (event, popup, button, index) {
                    popup.close()
                }
            }
        ],
        width: " 90%", //"300px" | 50% 设置主体宽度度
        height: "auto", //"300px" | 50%\ auto 设置主体高度（不包括头和脚）
        fullScreen: false, // true | false 全屏显示
        modal: false, //true 模态 | false 非模态（点击遮罩不移除弹出层）
        className: "",//添加自定义样式名
        callback: {
            "clickCallbackExample": function (event, popup) {//在属于popup内部dom上自定义data-callback="clickCallbackExample"属性,点击后会回调执行这里
            }
        }
    }
 */
(function ($) {
	$.popup = function (options) {
		return new popup(options)
	};
	var popup = function (options) {
		var settings = {
			close: "remove",// remove | hide | function 关闭popup执行的真正函数,close相当于关闭弹出的函数别名
			title: "", // string | jq对象
			content: "", // string | jq对象
			button: [],//[]空数组时没有按钮
			width: "90%", //"300px" | 50% 设置主体宽度度
			height: "auto", //"300px" | 50%\ auto 设置主体高度（不包括头和脚）
			fullScreen: false, // true | false 全屏显示
			modal: false, //true 模态 | false 非模态（点击遮罩不移除弹出层）
			callback: null,
			className: ""
		};
		this.options = $.extend(settings, options)
		this.init()
	}
	popup.prototype =
	{
		init: function () {
			var _this = this;
			_this.$element = $(_this.createHtml());
			_this.components = {
				"oMask": _this.$element,
				"oContainer": $(".popup-container", _this.$element),
				"oHeader": $(".popup-header", _this.$element),
				"oContent": $(".popup-content", _this.$element),
				"oFooter": $(".popup-footer", _this.$element)
			}

			//加入各种信息
			//全屏
			if (_this.options.fullScreen) {
				_this.components.oMask.addClass("full-screen")
			} else {
				_this.components.oContainer.width(_this.options.width)
			}
			//标题
			if (_this.options.title) {
				_this.components.oHeader.html(_this.options.title)
			}
			//内容
			if (_this.options.content) {
				_this.components.oContent.html(_this.options.content)
			}
			//按钮
			if (_this.options.button.length) {
				var btnArr = _this.options.button;
				var btnHtml = ""
				for (var i in btnArr) {
					btnHtml += ('<li style="width:' + (100 / btnArr.length) + '%;"><a href="javascript:;">' + btnArr[i]['text'] + '</a></li>')
				}
				_this.components.oFooter.html(btnHtml)
			}

			_this.$element.appendTo("body")

			//给按钮绑事件
			$(".popup-footer>li", _this.$element).each(function (index, button) {
				$(button).on("click", function (event) {
					var action = _this.options.button[index]["action"];
					if (typeof action == "function") {
						action(event, _this, button, index)
					}
				})
			})

			//给回调函数和遮罩模态弹出绑定响应事件
			$(_this.$element).on({
				"click": function (event) {
					var _element = event.target;
					//data-role="mask"事件响应,移除非模态弹出层
					if (($(_element).data("role") == "mask") && (_this.options.modal == false)) {
						_this.close()
					}
					//再popup上自定义的属性data-callback="****"执行回调函数
					if ($(_element).data("callback")) {
						var callback = _this.options.callback && _this.options.callback[$(_element).data("callback")]
						if (typeof callback == "function") {
							callback(event, _this)
						}
					}
				}
			})
			//
			$(window).on("resize", function () {
				//窗体变化保持居中
				if (_this.$element.hasClass("is-visible")) {
					_this.show()
				}
			})
		},
		//组装弹出层模版
		createHtml: function () {
			var _this = this, template = []
			//遮罩容器
			template.push('<div class="popup" data-role="mask"><div class="popup-container">')
			//标题
			template.push('<div class="popup-header"></div>')
			//内容
			template.push('<div class="popup-content"></div>')
			//底部按钮区域
			template.push('<ul class="popup-footer"></ul>')
			return template.join("")
		},
		show: function () {
			var _this = this;
			_this.$element.addClass("is-visible");
			_this.$element.addClass(_this.options.className)
			//全屏
			if (_this.options.fullScreen) {
				_this.components.oContent.height($(window).height() - _this.components.oHeader.height() - _this.components.oFooter.height())
			} else {
				var maxHeight = _this.options.height
				if (_this.options.height == "auto") {
					// 保证popup最高时留 上下40px
					maxHeight = $(window).height() - 80
					//头部和脚部固定中间内容滚动
					if (_this.components.oContainer.height() > maxHeight) {
						_this.components.oContent.height(maxHeight - _this.components.oHeader.height() - _this.components.oFooter.height())
					}
				} else {
					//主体高度（不包括头和脚）
					_this.components.oContent.height(maxHeight)
				}
				//上下居中
				_this.components.oContainer.css("margin-top", -_this.components.oContainer.height() / 2)
			}
		},
		hide: function () {
			this.$element.removeClass("is-visible")
		},
		remove: function () {
			this.$element.remove()
		},
		close: function () {
			var close = this.options.close
			if (typeof  close == "string") {
				switch (close) {
					case "remove":
						this.remove();
						break;
					case "hide":
						this.hide();
						break;
				}
			}
			if (typeof close == "function") {
				close(event, this)
			}
		}
	}
}($));
